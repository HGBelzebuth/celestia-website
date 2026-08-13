<?php
/**
 * referral_cron.php — Celestia-WoW
 * ============================================================
 * Cron job exécuté toutes les 10 minutes.
 * Commande crontab :
 *   [CRON] * /10 * * * * php /var/www/website/referral_cron.php >> /var/log/celestia_referral.log 2>&1
 *
 * Workflow :
 *  1. Récupère les parrainages en token_status='used', referral_status='pending'
 *  2. Vérifie les conditions de validation pour chaque filleul :
 *       - temps de jeu total >= 10 800 secondes (3h)
 *       - au moins 2 personnages de niveau >= 20
 *  3. Si conditions remplies → passe en referral_status='eligible'
 *  4. Attribution des récompenses (DP parrain, coupons boutique pré-stockés)
 *  5. Envoi des emails (parrain : code_en_jeu id=3)
 *  6. Passe en referral_status='rewarded', reward_given=1
 *  7. Expire les tokens expirés non utilisés
 * ============================================================
 */

declare(strict_types=1);

// ── Sécurité : exécution CLI uniquement ──────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Accès refusé.');
}

// ── Configuration ─────────────────────────────────────────────────────────────
$cfg = [
    'host'     => '185.246.86.164',
    'user'     => 'website',
    'pass'     => 'website',
    'db_web'   => 'R0_Website',
    'db_auth'  => 'R0_Auth',
    'db_chars' => 'R1_Chars',
    'db_eluna' => 'R1_Eluna',
    'mail_from'=> 'noreply@celestia-wow.com',
    'site_name'=> 'Celestia-WoW',
    'site_url' => 'https://celestia-wow.com',
];

$log_prefix = '[' . date('Y-m-d H:i:s') . '] REFERRAL_CRON : ';

function cron_log(string $msg): void
{
    global $log_prefix;
    echo $log_prefix . $msg . PHP_EOL;
}

// ── Connexions PDO ─────────────────────────────────────────────────────────────
$pdo_opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $web   = new PDO("mysql:host={$cfg['host']};dbname={$cfg['db_web']};charset=utf8mb4",
                     $cfg['user'], $cfg['pass'], $pdo_opts);
    $auth  = new PDO("mysql:host={$cfg['host']};dbname={$cfg['db_auth']};charset=utf8mb4",
                     $cfg['user'], $cfg['pass'], $pdo_opts);
    $chars = new PDO("mysql:host={$cfg['host']};dbname={$cfg['db_chars']};charset=utf8mb4",
                     $cfg['user'], $cfg['pass'], $pdo_opts);
    $eluna = new PDO("mysql:host={$cfg['host']};dbname={$cfg['db_eluna']};charset=utf8mb4",
                     $cfg['user'], $cfg['pass'], $pdo_opts);
} catch (PDOException $e) {
    cron_log('ERREUR connexion DB : ' . $e->getMessage());
    exit(1);
}

// ── 1. Expirer les tokens non utilisés ────────────────────────────────────────
$web->exec("
    UPDATE `referral_tokens`
    SET `token_status` = 'expired', `referral_status` = 'expired'
    WHERE `token_status` = 'generated'
      AND `expires_at` < NOW()
");
cron_log('Tokens expirés mis à jour.');

// ── 2. Récupérer les parrainages à vérifier ───────────────────────────────────
$stmt = $web->prepare("
    SELECT *
    FROM `referral_tokens`
    WHERE `token_status` = 'used'
      AND `referral_status` = 'pending'
      AND `referee_username` IS NOT NULL
");
$stmt->execute();
$pending_referrals = $stmt->fetchAll();

if (empty($pending_referrals)) {
    cron_log('Aucun parrainage en attente.');
    exit(0);
}

cron_log(count($pending_referrals) . ' parrainage(s) en attente de validation.');

// ── Charger les récompenses depuis referral_rewards ───────────────────────────
$rewards = [];
$rew_stmt = $web->query("SELECT * FROM `referral_rewards` WHERE `active` = 1");
foreach ($rew_stmt->fetchAll() as $r) {
    $rewards[(int)$r->id] = $r;
}

// ── Charger la config ─────────────────────────────────────────────────────────
$ref_cfg = $web->query("SELECT * FROM `referral_config` LIMIT 1")->fetch();
$coupon_duration_sponsor = (int)($ref_cfg->sponsor_discount_days ?? 90);
$coupon_duration_referee = (int)($ref_cfg->referee_discount_days ?? 30);

// ── Vérifier/ajouter colonne target dans referral_ingame_codes ────────────────
$_col = $web->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='referral_ingame_codes' AND COLUMN_NAME='target'")->fetchColumn();
if (!(int)$_col) {
    $web->exec("ALTER TABLE `referral_ingame_codes` ADD COLUMN `target` ENUM('sponsor','referee') NOT NULL DEFAULT 'referee' AFTER `parent_code`");
    cron_log("Colonne target ajoutée à referral_ingame_codes.");
}

// ── Helper : générer un code unique RF ───────────────────────────────────────
function gen_unique_rf(PDO $web): string
{
    $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $unique = ''; $attempts = 0;
    do {
        $unique = 'RF';
        for ($i = 0; $i < 8; $i++) $unique .= $pool[random_int(0, strlen($pool)-1)];
        $dup = $web->prepare("SELECT id FROM referral_ingame_codes WHERE unique_code=? LIMIT 1");
        $dup->execute([$unique]);
        $attempts++;
    } while ($dup->fetch() && $attempts < 20);
    return $unique;
}

// ── Helper : générer un code boutique unique ─────────────────────────────────
function gen_unique_coupon(PDO $web, string $parent_code, string $prefix, string $expires_at): ?string
{
    $p = $web->prepare("SELECT `type`, `value`, `currency`, `min_amount` FROM `store_coupons` WHERE `code` = ? LIMIT 1");
    $p->execute([$parent_code]);
    $parent = $p->fetch();
    if (!$parent) { cron_log("  ⚠ Code parent [{$parent_code}] introuvable dans store_coupons."); return null; }

    $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $unique = ''; $attempts = 0;
    do {
        $unique = $prefix;
        for ($i = 0; $i < 8; $i++) $unique .= $pool[random_int(0, strlen($pool)-1)];
        $dup = $web->prepare("SELECT id FROM `store_coupons` WHERE `code` = ? LIMIT 1");
        $dup->execute([$unique]);
        $attempts++;
    } while ($dup->fetch() && $attempts < 20);

    $web->prepare("INSERT INTO `store_coupons` (`code`, `type`, `value`, `currency`, `min_amount`, `expires_at`, `active`) VALUES (?, ?, ?, ?, ?, ?, 1)")
        ->execute([$unique, $parent->type, $parent->value, $parent->currency, $parent->min_amount, $expires_at]);
    return $unique;
}

// ── Helper : envoyer un email HTML via SMTP Gmail ────────────────────────────
function send_mail(string $to, string $subject, string $html_body, array $cfg): bool
{
    $smtp_host = 'ssl://smtp.gmail.com';
    $smtp_port = 465;
    $smtp_user = 'wowcelestia@gmail.com';
    $smtp_pass = 'kyeedaeeqeaqhzon';
    $from_addr = 'wowcelestia@gmail.com';
    $from_name = $cfg['site_name'];

    $errno = 0; $errstr = '';
    $sock = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$sock) { cron_log("SMTP connect failed: {$errstr}"); return false; }

    $read = function() use ($sock): string { return fgets($sock, 512); };
    $send = function(string $cmd) use ($sock): void { fwrite($sock, $cmd . "\r\n"); };

    $read(); // 220 banner
    $send("EHLO celestia-wow.com");
    $caps = '';
    while ($line = $read()) { $caps .= $line; if ($line[3] === ' ') break; }

    $send("AUTH LOGIN");
    $read(); // 334
    $send(base64_encode($smtp_user));
    $read(); // 334
    $send(base64_encode($smtp_pass));
    $auth_resp = $read(); // 235 or 535
    if (strpos($auth_resp, '235') === false) {
        cron_log("SMTP AUTH failed: " . trim($auth_resp));
        fclose($sock); return false;
    }

    $send("MAIL FROM:<{$from_addr}>");
    $read();
    $send("RCPT TO:<{$to}>");
    $rcpt = $read();
    if (strpos($rcpt, '250') === false) {
        cron_log("SMTP RCPT failed: " . trim($rcpt));
        fclose($sock); return false;
    }

    $send("DATA");
    $read(); // 354

    // Encode subject
    $subj_enc = '=?utf-8?B?' . base64_encode($subject) . '?=';

    $boundary = md5(uniqid('', true));
    $msg  = "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=utf-8\r\n";
    $msg .= "From: =?utf-8?B?" . base64_encode($from_name) . "?= <{$from_addr}>\r\n";
    $msg .= "To: {$to}\r\n";
    $msg .= "Subject: {$subj_enc}\r\n";
    $msg .= "\r\n";
    $msg .= $html_body . "\r\n";
    $msg .= ".";

    $send($msg);
    $data_resp = $read(); // 250
    $send("QUIT");
    fclose($sock);

    return strpos($data_resp, '250') !== false;
}

// ── Helper : logger une récompense ───────────────────────────────────────────
function log_reward(PDO $web, string $username, string $type, int $reward_id, string $token): void
{
    $s = $web->prepare("
        INSERT INTO `referral_reward_logs` (`username`, `reward_type`, `reward_id`, `token`)
        VALUES (?, ?, ?, ?)
    ");
    $s->execute([$username, $type, $reward_id, $token]);
}

// ── Helper : construire email parrain ────────────────────────────────────────
function build_sponsor_email(string $sponsor, string $referee, string $token_code,
                              string $code_en_jeu, ?string $boutique_code, array $cfg): string
{
    $s   = htmlspecialchars($sponsor, ENT_QUOTES, 'UTF-8');
    $r   = htmlspecialchars($referee, ENT_QUOTES, 'UTF-8');
    $t   = htmlspecialchars($token_code, ENT_QUOTES, 'UTF-8');
    $c   = htmlspecialchars($code_en_jeu, ENT_QUOTES, 'UTF-8');
    $sn  = htmlspecialchars($cfg['site_name'], ENT_QUOTES, 'UTF-8');
    $su  = htmlspecialchars($cfg['site_url'], ENT_QUOTES, 'UTF-8');
    $bc  = $boutique_code ? htmlspecialchars($boutique_code, ENT_QUOTES, 'UTF-8') : null;
    $boutique_block = $bc
        ? '<div style="background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.28);border-radius:8px;padding:18px;margin-bottom:20px;text-align:center"><div style="font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;color:#8a7030;margin-bottom:10px">Code boutique &mdash; usage unique</div><div style="font-family:Courier New,monospace;font-size:1.4rem;letter-spacing:.25em;font-weight:700;color:#f5e070">' . $bc . '</div><div style="font-size:.8rem;color:#8a7030;margin-top:8px">S&apos;applique automatiquement en boutique</div></div>'
        : '<div style="padding:12px 14px;background:rgba(93,190,122,.06);border:1px solid rgba(93,190,122,.2);border-radius:6px;font-size:.88rem;color:#7dd89a;margin-bottom:20px">Coupon boutique activ&eacute; dans votre <a href="' . $su . '/fr/user/panel" style="color:#5dbe7a;text-decoration:underline">espace parrain sur le panel</a>.</div>';

    return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Parrainage valid&eacute; &mdash; {$sn}</title><style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#06111c;font-family:Georgia,serif;color:#8da5bb}
a{color:#6a8aaa;text-decoration:none}
</style></head>
<body style="margin:0;padding:32px 16px;background:#06111c">
<div style="max-width:580px;margin:0 auto;background:#0a1a2c;border:1px solid rgba(180,200,220,.16);border-radius:12px;overflow:hidden">

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#1a1200 0%,#0d0a00 100%);padding:32px;border-bottom:1px solid rgba(200,168,75,.2)">
    <div style="font-size:.72rem;letter-spacing:.18em;text-transform:uppercase;color:#7a6020;margin-bottom:8px">Celestia&nbsp;WoW &mdash; Parrainage</div>
    <h1 style="font-size:1.4rem;color:#f5e070;font-weight:normal;line-height:1.3">
      &#127881;&nbsp;F&eacute;licitations,&nbsp;{$s}&nbsp;!<br>
      <span style="font-size:1rem;color:#c8a84b">Votre parrainage est valid&eacute;.</span>
    </h1>
  </div>

  <!-- Body -->
  <div style="padding:28px 32px;line-height:1.75">

    <p style="margin-bottom:16px">Votre filleul&nbsp;<strong style="color:#c8d4e0">{$r}</strong> a rempli toutes les conditions
    (3&nbsp;h de jeu, 2&nbsp;personnages niveau&nbsp;&ge;&nbsp;20) gr&acirc;ce &agrave; votre token&nbsp;<strong style="color:#c8d4e0">{$t}</strong>.</p>

    <!-- Code monture -->
    <p style="font-size:.8rem;letter-spacing:.12em;text-transform:uppercase;color:#7a6020;margin-bottom:10px">Votre r&eacute;compense &mdash; code monture parrain</p>
    <div style="background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.28);border-radius:8px;padding:20px;margin-bottom:20px;text-align:center">
      <div style="font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;color:#8a7030;margin-bottom:10px">Code &agrave; usage unique &mdash; NPC Tyra&euml;l</div>
      <div style="font-family:Courier New,monospace;font-size:1.6rem;letter-spacing:.3em;font-weight:700;color:#f5e070">{$c}</div>
    </div>

    <!-- Instructions -->
    <p style="font-size:.8rem;letter-spacing:.12em;text-transform:uppercase;color:#7a6020;margin-bottom:10px">Comment utiliser votre code en jeu</p>
    <ol style="padding-left:20px;margin-bottom:20px;font-size:.93rem;line-height:2.1">
      <li>Connectez-vous &agrave; <strong style="color:#c8d4e0">Celestia&nbsp;WoW</strong> avec n&apos;importe quel personnage.</li>
      <li>Trouvez le <strong style="color:#c8d4e0">PNJ Tyra&euml;l</strong> en jeu (zone de d&eacute;part ou ville principale).</li>
      <li>Faites un <strong style="color:#c8d4e0">clic droit</strong> sur lui &rarr; s&eacute;lectionnez <em>&laquo;&thinsp;Je souhaite entrer un code promo.&thinsp;&raquo;</em></li>
      <li>Saisissez le code ci-dessus <strong>exactement</strong> (majuscules) et confirmez avec&nbsp;<em>Accepter</em>.</li>
      <li>Votre monture parrain est cr&eacute;dit&eacute;e <strong style="color:#c8d4e0">instantan&eacute;ment</strong>&nbsp;!</li>
    </ol>

    <!-- Coupon boutique -->
    <!-- Coupon boutique (code unique si disponible) -->
    {$boutique_block}

    <!-- Avertissement -->
    <div style="padding:12px 16px;background:rgba(200,168,75,.06);border:1px solid rgba(200,168,75,.18);border-radius:6px;font-size:.86rem;color:#c8a84b">
      &#9888;&nbsp;Ce code est <strong>strictement personnel</strong> et ne peut &ecirc;tre utilis&eacute; <strong>qu&apos;une seule fois</strong>. Ne le partagez pas.
    </div>

  </div>

  <!-- Footer -->
  <div style="padding:16px 32px;background:rgba(0,0,0,.3);font-size:.8rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.05)">
    <a href="{$su}">{$sn}</a> &mdash; Email automatique, merci de ne pas r&eacute;pondre.
  </div>

</div>
</body></html>
HTML;
}

// ── Helper : construire email filleul ────────────────────────────────────────
function build_referee_email(string $referee, array $codes_en_jeu, array $cfg): string
{
    $r  = htmlspecialchars($referee, ENT_QUOTES, 'UTF-8');
    $sn = htmlspecialchars($cfg['site_name'], ENT_QUOTES, 'UTF-8');
    $su = htmlspecialchars($cfg['site_url'], ENT_QUOTES, 'UTF-8');

    $codes_html = '';
    foreach ($codes_en_jeu as $c) {
        $codes_html .= '<div class="code">' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{margin:0;padding:0;background:#06111c;font-family:Georgia,serif;color:#8da5bb;}
.wrap{max-width:560px;margin:40px auto;background:#0a1a2c;border:1px solid rgba(180,200,220,.18);border-radius:10px;overflow:hidden;}
.hd{background:linear-gradient(135deg,#0d1f36,#0a1628);padding:28px 32px;border-bottom:1px solid rgba(180,200,220,.14);}
.hd h1{margin:0;font-size:1.3rem;color:#c8d4e0;letter-spacing:.08em;}
.bd{padding:28px 32px;line-height:1.7;}
.highlight{background:rgba(93,190,122,.07);border:1px solid rgba(93,190,122,.25);border-radius:8px;padding:16px 20px;margin:16px 0;}
.code{font-family:Courier,monospace;font-size:1.3rem;letter-spacing:.25em;font-weight:bold;color:#7dd89a;text-align:center;padding:6px 0;}
.note{margin-top:14px;padding:10px 14px;background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.2);border-radius:6px;font-size:.92rem;color:#c8a84b;}
.ft{padding:14px 32px;background:rgba(0,0,0,.3);font-size:.85rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.05);}
.ft a{color:#6a8aaa;text-decoration:none;}
</style></head>
<body><div class="wrap">
<div class="hd"><h1>&#127873; Vos r&eacute;compenses filleul sont disponibles !</h1></div>
<div class="bd">
<p>Bonjour <strong style="color:#c8d4e0">{$r}</strong>,</p>
<p style="margin-top:12px">F&eacute;licitations ! Vous avez rempli toutes les conditions de parrainage (3h de jeu, 2 personnages niveau&nbsp;&ge;&nbsp;20).</p>
<p style="margin-top:14px">Voici vos codes en jeu &agrave; r&eacute;clamer :</p>
<div class="highlight">
  <div style="font-size:.78rem;color:#3a7a4a;letter-spacing:.1em;text-transform:uppercase;margin-bottom:10px;">Codes en jeu — &agrave; donner au NPC Tyra&euml;l</div>
  {$codes_html}
</div>
<div class="note">&#9888; Rendez-vous aupr&egrave;s du NPC <strong>Tyra&euml;l</strong> en jeu et donnez-lui ces codes pour recevoir vos r&eacute;compenses.</div>
<p style="margin-top:16px;font-size:.92rem;color:#4a6278;">Votre coupon boutique filleul est &eacute;galement visible dans votre panel.</p>
</div>
<div class="ft"><a href="{$su}">{$sn}</a> &mdash; Email automatique.</div>
</div></body></html>
HTML;
}

// ═════════════════════════════════════════════════════════════════════════════
// ── 3. Traitement de chaque parrainage en attente ────────────────────────────
// ═════════════════════════════════════════════════════════════════════════════

foreach ($pending_referrals as $ref) {

    $referee_username = $ref->referee_username;
    $sponsor_username = $ref->sponsor_username;
    $token_code       = $ref->token;

    cron_log("Vérification filleul [{$referee_username}] — token [{$token_code}]");

    // ── 3a. Récupérer l'account_id du filleul dans R0_Auth ────────────────────
    $acc_stmt = $auth->prepare("
        SELECT `id` FROM `account`
        WHERE `username` = ?
        LIMIT 1
    ");
    $acc_stmt->execute([strtoupper($referee_username)]);
    $acc_row = $acc_stmt->fetch();

    if (!$acc_row) {
        cron_log("  → Compte introuvable dans R0_Auth pour [{$referee_username}], ignoré.");
        continue;
    }
    $account_id = (int)$acc_row->id;

    // ── 3b. Récupérer les personnages du filleul dans R1_Chars ────────────────
    $chars_stmt = $chars->prepare("
        SELECT `level`, `totaltime`
        FROM `characters`
        WHERE `account` = ?
    ");
    $chars_stmt->execute([$account_id]);
    $char_rows = $chars_stmt->fetchAll();

    // Calculer temps de jeu total (somme de tous les persos) et nb persos >= niv 20
    $total_playtime_seconds = 0;
    $chars_level20_count    = 0;

    foreach ($char_rows as $c) {
        $total_playtime_seconds += (int)$c->totaltime;
        if ((int)$c->level >= 20) {
            $chars_level20_count++;
        }
    }

    $playtime_minutes = (int)floor($total_playtime_seconds / 60);
    $meets_playtime   = ($total_playtime_seconds >= 10800); // 180 min = 10800 sec
    $meets_chars      = ($chars_level20_count >= 2);

    cron_log(sprintf(
        "  → Temps jeu : %d min (%s) | Persos niv>=20 : %d (%s)",
        $playtime_minutes,
        $meets_playtime ? 'OK' : 'insuffisant',
        $chars_level20_count,
        $meets_chars ? 'OK' : 'insuffisant'
    ));

    // ── 3c. Si conditions non remplies → on passe, on reviendra au prochain cron ─
    if (!$meets_playtime || !$meets_chars) {
        continue;
    }

    // ── 3d. Conditions remplies → passage en 'eligible' puis 'rewarded' ──────
    $now = date('Y-m-d H:i:s');

    // Vérification anti-double attribution
    $already = $web->prepare("
        SELECT `reward_given` FROM `referral_tokens`
        WHERE `token` = ? AND `reward_given` = 1
        LIMIT 1
    ");
    $already->execute([$token_code]);
    if ($already->fetch()) {
        cron_log("  → Récompenses déjà attribuées pour [{$token_code}], ignoré.");
        continue;
    }

    cron_log("  → Conditions remplies. Attribution des récompenses…");

    // ── Marquer eligible ─────────────────────────────────────────────────────
    $web->prepare("
        UPDATE `referral_tokens`
        SET `referral_status` = 'eligible', `validated_at` = ?
        WHERE `token` = ?
    ")->execute([$now, $token_code]);

    // ════════════════════════════════════════════════════════════
    // RÉCOMPENSES PARRAIN
    // ════════════════════════════════════════════════════════════

    // --- DP parrain (referral_rewards id=1) ---
    if (isset($rewards[1])) {
        $dp_amount = (int)$rewards[1]->quantite;
        if ($dp_amount > 0) {
            $web->prepare("
                UPDATE `users` SET `dp` = `dp` + ?
                WHERE `username` = ?
            ")->execute([$dp_amount, $sponsor_username]);
            log_reward($web, $sponsor_username, 'dp', 1, $token_code);
            cron_log("  → Parrain [{$sponsor_username}] : +{$dp_amount} DP crédités.");
        }
    }

    // --- Coupon boutique parrain (referral_rewards id=2) — code unique par parrainage ---
    $unique_sp_coupon = null;
    if (isset($rewards[2]) && !empty($rewards[2]->code_boutique)) {
        $expires_sp = date('Y-m-d H:i:s', strtotime("+{$coupon_duration_sponsor} days"));
        $disc_exists = $web->prepare("
            SELECT id, unique_code FROM `referral_discounts`
            WHERE `username` = ? AND `type` = 'sponsor' AND `token_ref` = ?
            LIMIT 1
        ");
        $disc_exists->execute([$sponsor_username, $token_code]);
        $disc_row_sp = $disc_exists->fetch();
        if ($disc_row_sp) {
            $unique_sp_coupon = $disc_row_sp->unique_code;
        } else {
            $unique_sp_coupon = gen_unique_coupon($web, $rewards[2]->code_boutique, 'BSP', $expires_sp);
            if ($unique_sp_coupon) {
                $web->prepare("
                    INSERT INTO `referral_discounts`
                      (`username`, `type`, `store_coupon_id`, `unique_code`, `token_ref`, `starts_at`, `expires_at`)
                    VALUES (?, 'sponsor', 2, ?, ?, ?, ?)
                ")->execute([$sponsor_username, $unique_sp_coupon, $token_code, $now, $expires_sp]);
            }
        }
        if ($unique_sp_coupon) {
            log_reward($web, $sponsor_username, 'code_boutique', 2, $token_code);
            cron_log("  → Parrain [{$sponsor_username}] : coupon boutique unique [{$unique_sp_coupon}] activé.");
        }
    }

    // ════════════════════════════════════════════════════════════
    // RÉCOMPENSES FILLEUL
    // ════════════════════════════════════════════════════════════

    // --- Coupon boutique filleul (referral_rewards id=7) — code unique par parrainage ---
    $unique_ref_coupon = null;
    if (isset($rewards[7]) && !empty($rewards[7]->code_boutique)) {
        $expires_ref = date('Y-m-d H:i:s', strtotime("+{$coupon_duration_referee} days"));
        $disc_exists2 = $web->prepare("
            SELECT id, unique_code FROM `referral_discounts`
            WHERE `username` = ? AND `type` = 'referee' AND `token_ref` = ?
            LIMIT 1
        ");
        $disc_exists2->execute([$referee_username, $token_code]);
        $disc_row_rf = $disc_exists2->fetch();
        if ($disc_row_rf) {
            $unique_ref_coupon = $disc_row_rf->unique_code;
        } else {
            $unique_ref_coupon = gen_unique_coupon($web, $rewards[7]->code_boutique, 'BRF', $expires_ref);
            if ($unique_ref_coupon) {
                $web->prepare("
                    INSERT INTO `referral_discounts`
                      (`username`, `type`, `store_coupon_id`, `unique_code`, `token_ref`, `starts_at`, `expires_at`)
                    VALUES (?, 'referee', 7, ?, ?, ?, ?)
                ")->execute([$referee_username, $unique_ref_coupon, $token_code, $now, $expires_ref]);
            }
        }
        if ($unique_ref_coupon) {
            log_reward($web, $referee_username, 'code_boutique', 7, $token_code);
            cron_log("  → Filleul [{$referee_username}] : coupon boutique unique [{$unique_ref_coupon}] activé.");
        }
    }

    // ════════════════════════════════════════════════════════════
    // EMAILS
    // ════════════════════════════════════════════════════════════

    // --- Code unique monture parrain (referral_rewards id=3) ---
    $sp_unique_mount = null;
    if (isset($rewards[3]) && !empty($rewards[3]->code_en_jeu)) {
        $parent_mount = $rewards[3]->code_en_jeu; // LRTFY42M
        // Idempotence : code déjà généré pour ce token ?
        $code_ex = $web->prepare("SELECT unique_code FROM referral_ingame_codes WHERE token_ref=? AND referee_username=? AND target='sponsor' LIMIT 1");
        $code_ex->execute([$token_code, $sponsor_username]);
        $code_ex_row = $code_ex->fetch();
        if ($code_ex_row) {
            $sp_unique_mount = $code_ex_row->unique_code;
            cron_log("  → Parrain [{$sponsor_username}] : code monture déjà généré [{$sp_unique_mount}].");
        } else {
            $pchk = $eluna->prepare("SELECT code FROM player_code WHERE code=? AND enabled=1 LIMIT 1");
            $pchk->execute([$parent_mount]);
            if ($pchk->fetch()) {
                $prwd = $eluna->prepare("SELECT item_entry, quantity, money FROM player_code_rewards WHERE code=? LIMIT 1");
                $prwd->execute([$parent_mount]);
                $prwd_row = $prwd->fetch();
                if ($prwd_row) {
                    $sp_unique_mount = gen_unique_rf($web);
                    $eluna->prepare("INSERT INTO player_code (code,use_count,start_time,end_time,enabled,for_new_player) VALUES (?,1,NOW(),DATE_ADD(NOW(),INTERVAL 5 YEAR),1,0)")
                          ->execute([$sp_unique_mount]);
                    $eluna->prepare("INSERT INTO player_code_rewards (code,item_entry,quantity,money) VALUES (?,?,?,?)")
                          ->execute([$sp_unique_mount, $prwd_row->item_entry, $prwd_row->quantity, $prwd_row->money]);
                    $web->prepare("INSERT INTO referral_ingame_codes (token_ref,referee_username,reward_id,reward_nom,unique_code,parent_code,target) VALUES (?,?,?,?,?,?,'sponsor')")
                        ->execute([$token_code, $sponsor_username, 3, $rewards[3]->nom, $sp_unique_mount, $parent_mount]);
                    log_reward($web, $sponsor_username, 'code_en_jeu', 3, $token_code);
                    cron_log("  → Parrain [{$sponsor_username}] : code monture unique généré [{$sp_unique_mount}].");
                }
            }
        }
    }

    // --- Email parrain ---
    $sponsor_email = '';
    $sp_email_stmt = $web->prepare("SELECT `email` FROM `users` WHERE `username` = ? LIMIT 1");
    $sp_email_stmt->execute([$sponsor_username]);
    $sp_row = $sp_email_stmt->fetch();
    if ($sp_row && filter_var($sp_row->email, FILTER_VALIDATE_EMAIL)) {
        $sponsor_email = $sp_row->email;
    }

    if ($sponsor_email && $sp_unique_mount !== null) {
        $mail_html = build_sponsor_email(
            $sponsor_username,
            $referee_username,
            $token_code,
            $sp_unique_mount,
            $unique_sp_coupon ?? null,
            $cfg
        );
        $sent = send_mail(
            $sponsor_email,
            "[{$cfg['site_name']}] Votre parrainage a été validé !",
            $mail_html,
            $cfg
        );
        cron_log("  → Email parrain [{$sponsor_email}] : " . ($sent ? 'envoyé' : 'ÉCHEC'));
    }

    // --- Email filleul : notification coupon boutique (codes 4-6 déjà envoyés à l'activation du compte) ---
    $referee_email = $ref->referee_email ?? '';
    if (empty($referee_email)) {
        $ref_email_stmt = $web->prepare("SELECT `email` FROM `users` WHERE `username` = ? LIMIT 1");
        $ref_email_stmt->execute([$referee_username]);
        $ref_row = $ref_email_stmt->fetch();
        if ($ref_row) $referee_email = $ref_row->email;
    }

    if (filter_var($referee_email, FILTER_VALIDATE_EMAIL) && isset($rewards[7])) {
        $coup_code  = htmlspecialchars((string)($unique_ref_coupon ?? ''), ENT_QUOTES, 'UTF-8');
        $disc_pct   = (int)($rewards[7]->quantite ?? 20);
        $exp_ref_fr = date('d/m/Y', strtotime("+{$coupon_duration_referee} days"));
        $r_esc      = htmlspecialchars($referee_username, ENT_QUOTES, 'UTF-8');
        $sn_esc     = htmlspecialchars($cfg['site_name'], ENT_QUOTES, 'UTF-8');
        $su_esc     = htmlspecialchars($cfg['site_url'],  ENT_QUOTES, 'UTF-8');

        $mail_ref_html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
        $mail_ref_html .= 'body{margin:0;padding:0;background:#06111c;font-family:Georgia,serif;color:#8da5bb;}';
        $mail_ref_html .= '.wrap{max-width:560px;margin:40px auto;background:#0a1a2c;border:1px solid rgba(180,200,220,.18);border-radius:10px;overflow:hidden;}';
        $mail_ref_html .= '.hd{background:linear-gradient(135deg,#0d1f36,#0a1628);padding:28px 32px;border-bottom:1px solid rgba(180,200,220,.14);}';
        $mail_ref_html .= '.hd h1{margin:0;font-size:1.3rem;color:#c8d4e0;letter-spacing:.08em;}';
        $mail_ref_html .= '.bd{padding:28px 32px;line-height:1.7;}';
        $mail_ref_html .= '.box{background:rgba(93,190,122,.08);border:1px solid rgba(93,190,122,.3);border-radius:8px;padding:16px 20px;margin:16px 0;text-align:center;}';
        $mail_ref_html .= '.code{font-family:Courier,monospace;font-size:1.4rem;letter-spacing:.25em;font-weight:bold;color:#7dd89a;}';
        $mail_ref_html .= '.ft{padding:14px 32px;background:rgba(0,0,0,.3);font-size:.85rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.05);}';
        $mail_ref_html .= '.ft a{color:#6a8aaa;text-decoration:none;}';
        $mail_ref_html .= '</style></head><body><div class="wrap">';
        $mail_ref_html .= '<div class="hd"><h1>&#127873; Votre coupon boutique filleul est disponible !</h1></div>';
        $mail_ref_html .= '<div class="bd">';
        $mail_ref_html .= "<p>Bonjour <strong style=\"color:#c8d4e0\">{$r_esc}</strong>,</p>";
        $mail_ref_html .= "<p style=\"margin-top:12px\">F&eacute;licitations&nbsp;! Vous avez atteint les conditions de parrainage (3h de jeu, 2 personnages niveau&nbsp;&ge;&nbsp;20).</p>";
        $mail_ref_html .= "<p style=\"margin-top:12px\">Votre coupon boutique <strong style=\"color:#c8d4e0\">{$disc_pct}%</strong> de r&eacute;duction est maintenant actif&nbsp;:</p>";
        $mail_ref_html .= '<div class="box">';
        $mail_ref_html .= '<div style="font-size:.78rem;color:#3a7a4a;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;">Code boutique</div>';
        $mail_ref_html .= "<div class=\"code\">{$coup_code}</div>";
        $mail_ref_html .= "<div style=\"font-size:.82rem;color:#4a8a5a;margin-top:8px;\">Valable jusqu'au {$exp_ref_fr}</div>";
        $mail_ref_html .= '</div>';
        $mail_ref_html .= "<p style=\"margin-top:14px;font-size:.92rem;color:#4a6278;\">Retrouvez &eacute;galement ce coupon dans votre espace filleul sur le panel.</p>";
        $mail_ref_html .= '</div>';
        $mail_ref_html .= "<div class=\"ft\"><a href=\"{$su_esc}\">{$sn_esc}</a> &mdash; Email automatique.</div>";
        $mail_ref_html .= '</div></body></html>';

        $sent_ref = send_mail($referee_email, "[{$cfg['site_name']}] Votre coupon boutique filleul est disponible !", $mail_ref_html, $cfg);
        cron_log("  → Email filleul [{$referee_email}] : " . ($sent_ref ? 'envoyé' : 'ÉCHEC'));
    }

    // ── Marquer rewarded ─────────────────────────────────────────────────────
    $web->prepare("
        UPDATE `referral_tokens`
        SET `referral_status` = 'rewarded',
            `reward_given`    = 1,
            `reward_given_at` = ?
        WHERE `token` = ?
    ")->execute([$now, $token_code]);

    cron_log("  → Token [{$token_code}] : statut → rewarded. ✓");
}

cron_log('Cron terminé.');
exit(0);
