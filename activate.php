<?php
/**
 * activate.php — Celestia-WoW
 * URL dans l'email : https://celestia-wow.com/activate.php?t=TOKEN
 */

$db_host   = '185.246.86.164';
$db_user   = 'website';
$db_pass   = 'website';
$db_web    = 'R0_Website';
$db_auth   = 'R0_Auth';
$db_eluna  = 'R1_Eluna';
$site_name = 'Celestia-WoW';
$site_url  = 'https://celestia-wow.com';
$mail_from = 'noreply@celestia-wow.com';

$login_url    = 'https://celestia-wow.com/en/login';
$register_url = 'https://celestia-wow.com/en/register';

$token = isset($_GET['t']) ? preg_replace('/[^A-Za-z0-9]/', '', $_GET['t']) : '';
if (empty($token)) { header('Location: ' . $login_url); exit; }

try {
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
    $web  = new PDO("mysql:host={$db_host};dbname={$db_web};charset=utf8mb4",  $db_user, $db_pass, $opt);
    $auth = new PDO("mysql:host={$db_host};dbname={$db_auth};charset=utf8mb4", $db_user, $db_pass, $opt);
} catch (Exception $e) {
    die('Erreur connexion base de données.');
}

$stmt = $web->prepare("SELECT * FROM email_confirmations WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) { header('Location: ' . $register_url . '?err=invalid'); exit; }

if (strtotime($row->expires_at) < time()) {
    $web->prepare("UPDATE email_confirmations SET status='expired' WHERE token=?")->execute([$token]);
    header('Location: ' . $register_url . '?err=expired');
    exit;
}

if ($row->status === 'pending') {
    $now = date('Y-m-d H:i:s');

    /* 1. Débloquer le compte */
    $auth->prepare("UPDATE account SET locked=0 WHERE username=?")->execute([strtoupper($row->username)]);
    $web->prepare("UPDATE email_confirmations SET status='confirmed', confirmed_at=? WHERE token=?")->execute([$now, $token]);

    /* 2. Parrainage — marquer le token utilisé et générer les codes uniques filleul */
    $ref_stmt = $web->prepare(
        "SELECT * FROM referral_tokens WHERE referee_username = ? AND token_status = 'generated' LIMIT 1"
    );
    $ref_stmt->execute([$row->username]);
    $pending_ref = $ref_stmt->fetch();

    if ($pending_ref) {
        /* Marquer le token comme utilisé */
        $web->prepare("UPDATE referral_tokens SET token_status='used', used_at=? WHERE token=?")
            ->execute([$now, $pending_ref->token]);

        /* Auto-create table referral_ingame_codes */
        $web->exec("CREATE TABLE IF NOT EXISTS `referral_ingame_codes` (
          `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `token_ref`        CHAR(8) NOT NULL,
          `referee_username` VARCHAR(64) NOT NULL,
          `reward_id`        SMALLINT UNSIGNED NOT NULL,
          `reward_nom`       VARCHAR(120) NOT NULL DEFAULT '',
          `unique_code`      VARCHAR(32) NOT NULL,
          `parent_code`      VARCHAR(255) NOT NULL DEFAULT '',
          `redeemed`         TINYINT(1) NOT NULL DEFAULT 0,
          `redeemed_at`      DATETIME DEFAULT NULL,
          `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_code` (`unique_code`),
          KEY `idx_token`   (`token_ref`),
          KEY `idx_referee` (`referee_username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        /* Idempotence : si des codes existent déjà pour ce token, ne pas regénérer */
        $already = $web->prepare("SELECT COUNT(*) FROM referral_ingame_codes WHERE token_ref=?");
        $already->execute([$pending_ref->token]);
        $already_done = ((int)$already->fetchColumn() > 0);

        /* Connexion R1_Eluna pour la création des codes en jeu */
        $eluna = null;
        if (!$already_done) {
            try {
                $eluna = new PDO("mysql:host={$db_host};dbname={$db_eluna};charset=utf8mb4", $db_user, $db_pass, $opt);
            } catch (Exception $e) { /* continue sans codes uniques */ }
        }

        $generated_codes = [];

        if ($eluna && !$already_done) {
            /* Récompenses filleul avec code_en_jeu (ids 4, 5, 6) */
            $rwd_stmt = $web->prepare("SELECT * FROM referral_rewards WHERE id IN (4,5,6) AND active=1 ORDER BY id ASC");
            $rwd_stmt->execute();
            $rwd_rows = $rwd_stmt->fetchAll();

            $chars_pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

            foreach ($rwd_rows as $rw) {
                if (empty($rw->code_en_jeu)) continue;

                /* Vérifier que le parent code existe dans player_code et est actif */
                $pchk = $eluna->prepare("SELECT code FROM player_code WHERE code=? LIMIT 1");
                $pchk->execute([$rw->code_en_jeu]);
                if (!$pchk->fetch()) continue;

                /* Récupérer la récompense associée au code parent */
                $prwd = $eluna->prepare("SELECT item_entry, quantity, money FROM player_code_rewards WHERE code=? LIMIT 1");
                $prwd->execute([$rw->code_en_jeu]);
                $prwd_row = $prwd->fetch();
                if (!$prwd_row) continue;

                /* Générer un code unique : RF + 8 caractères */
                $unique = ''; $attempts = 0;
                do {
                    $unique = 'RF';
                    for ($i = 0; $i < 8; $i++) $unique .= $chars_pool[random_int(0, strlen($chars_pool)-1)];
                    $dup = $web->prepare("SELECT id FROM referral_ingame_codes WHERE unique_code=? LIMIT 1");
                    $dup->execute([$unique]);
                    $attempts++;
                } while ($dup->fetch() && $attempts < 20);

                /* Insérer dans R1_Eluna.player_code : usage unique, non réservé aux nouveaux */
                $eluna->prepare(
                    "INSERT INTO player_code (code, use_count, start_time, end_time, enabled, for_new_player)
                     VALUES (?, 1, NOW(), DATE_ADD(NOW(), INTERVAL 5 YEAR), 1, 0)"
                )->execute([$unique]);

                /* Copier la récompense du parent dans player_code_rewards */
                $eluna->prepare(
                    "INSERT INTO player_code_rewards (code, item_entry, quantity, money) VALUES (?, ?, ?, ?)"
                )->execute([$unique, $prwd_row->item_entry, $prwd_row->quantity, $prwd_row->money]);

                /* Stocker en R0_Website pour affichage panel et traçabilité */
                $web->prepare(
                    "INSERT INTO referral_ingame_codes
                     (token_ref, referee_username, reward_id, reward_nom, unique_code, parent_code)
                     VALUES (?, ?, ?, ?, ?, ?)"
                )->execute([$pending_ref->token, $row->username, $rw->id, $rw->nom, $unique, $rw->code_en_jeu]);

                $generated_codes[] = ['nom' => $rw->nom, 'code' => $unique];
            }
        }

        /* Email avec les codes uniques */
        if (!empty($generated_codes) && filter_var($row->email, FILTER_VALIDATE_EMAIL)) {
            $u  = htmlspecialchars($row->username, ENT_QUOTES, 'UTF-8');
            $sn = htmlspecialchars($site_name,     ENT_QUOTES, 'UTF-8');
            $su = htmlspecialchars($site_url,      ENT_QUOTES, 'UTF-8');

            $codes_html = '';
            foreach ($generated_codes as $gc) {
                $codes_html .= '<div class="code">'   . htmlspecialchars($gc['code'], ENT_QUOTES, 'UTF-8') . '</div>'
                             . '<div class="code-nm">' . htmlspecialchars($gc['nom'],  ENT_QUOTES, 'UTF-8') . '</div>';
            }

            $mail_html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
            $mail_html .= 'body{margin:0;padding:0;background:#06111c;font-family:Georgia,serif;color:#8da5bb;}';
            $mail_html .= '.wrap{max-width:560px;margin:40px auto;background:#0a1a2c;border:1px solid rgba(180,200,220,.18);border-radius:10px;overflow:hidden;}';
            $mail_html .= '.hd{background:linear-gradient(135deg,#0d1f36,#0a1628);padding:28px 32px;border-bottom:1px solid rgba(180,200,220,.14);}';
            $mail_html .= '.hd h1{margin:0;font-size:1.3rem;color:#c8d4e0;letter-spacing:.08em;}';
            $mail_html .= '.bd{padding:28px 32px;line-height:1.7;}';
            $mail_html .= '.box{background:rgba(93,190,122,.07);border:1px solid rgba(93,190,122,.25);border-radius:8px;padding:16px 20px;margin:16px 0;}';
            $mail_html .= '.code{font-family:Courier,monospace;font-size:1.3rem;letter-spacing:.25em;font-weight:bold;color:#7dd89a;text-align:center;padding:4px 0;}';
            $mail_html .= '.code-nm{font-size:.85rem;color:#4a8a5a;text-align:center;margin-bottom:10px;font-style:italic;}';
            $mail_html .= '.note{margin-top:14px;padding:10px 14px;background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.2);border-radius:6px;font-size:.92rem;color:#c8a84b;}';
            $mail_html .= '.info{margin-top:16px;padding:12px 14px;background:rgba(180,200,220,.04);border:1px solid rgba(180,200,220,.1);border-radius:6px;font-size:.92rem;color:#8da5bb;}';
            $mail_html .= '.ft{padding:14px 32px;background:rgba(0,0,0,.3);font-size:.85rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.05);}';
            $mail_html .= '.ft a{color:#6a8aaa;text-decoration:none;}';
            $mail_html .= '</style></head><body><div class="wrap">';
            $mail_html .= "<div class=\"hd\"><h1>&#127873; Bienvenue {$u} — Vos codes personnels filleul !</h1></div>";
            $mail_html .= '<div class="bd">';
            $mail_html .= '<p>Votre compte a &eacute;t&eacute; activ&eacute; et votre parrainage pris en compte.<br>Voici vos codes <strong>personnels &agrave; usage unique</strong> &agrave; r&eacute;clamer en jeu&nbsp;:</p>';
            $mail_html .= '<div class="box">';
            $mail_html .= '<div style="font-size:.78rem;color:#3a7a4a;letter-spacing:.1em;text-transform:uppercase;margin-bottom:12px;">Codes personnels &mdash; NPC Tyra&euml;l &mdash; &agrave; usage unique</div>';
            $mail_html .= $codes_html;
            $mail_html .= '</div>';
            $mail_html .= '<div class="note">&#9888; Ces codes sont <strong>personnels et &agrave; usage unique</strong>. Ils sont aussi disponibles dans votre panel sur le site. Rendez-vous aupr&egrave;s du NPC <strong>Tyra&euml;l</strong> en jeu.</div>';
            $mail_html .= '<div class="info"><strong style="color:#c8d4e0">Prochaine &eacute;tape&nbsp;:</strong> pour d&eacute;bloquer votre coupon boutique, atteignez <strong style="color:#c8d4e0">3h de jeu</strong> et poss&eacute;dez <strong style="color:#c8d4e0">2 personnages niveau&nbsp;&ge;&nbsp;20</strong>. Vous recevrez un email d&egrave;s que votre coupon est disponible&nbsp;!</div>';
            $mail_html .= '</div>';
            $mail_html .= "<div class=\"ft\"><a href=\"{$su}\">{$sn}</a> &mdash; Email automatique.</div>";
            $mail_html .= '</div></body></html>';

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            $headers .= "From: {$site_name} <{$mail_from}>\r\n";
            mail($row->email, "[{$site_name}] Vos codes personnels de parrainage !", $mail_html, $headers);
        }
    }
}

header('Location: ' . $login_url . '?activated=1');
exit;
