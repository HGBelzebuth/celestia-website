<?php
/* ═══════════════════════════════════════════════════════════════════════
   PANEL — TRAITEMENTS PHP (avant tout rendu HTML)
   ► Action POST "unstuck"  : débloquage de personnage
   ► Action POST "transfer" : transfert de personnage (coût 50 DP)
   ► Action POST "referral" : génération de token de parrainage
═══════════════════════════════════════════════════════════════════════ */

$sess_id       = $this->session->userdata('wow_sess_id');
$sess_username = $this->session->userdata('blizz_sess_username');

/* ── Helpers de connexion ── */
$realm = $this->wowrealm->getRealms()->row();

$charDB = $this->wowrealm->realmConnection(
    $realm->username, $realm->password,
    $realm->hostname, $realm->char_database
);
$authDB = $this->wowrealm->realmConnection(
    $realm->username, $realm->password,
    $realm->hostname, $realm->auth_database ?? 'R0_Auth'
);
$webDB = $this->wowrealm->realmConnection(
    $realm->username, $realm->password,
    $realm->hostname, $realm->web_database ?? 'R0_Website'
);
$elunaDB = $this->wowrealm->realmConnection(
    $realm->username, $realm->password,
    $realm->hostname, 'R1_Eluna'
);

/* ── Résultats des actions ── */
$unstuck_msg   = ''; $unstuck_type  = '';
$transfer_msg  = ''; $transfer_type = '';
$referral_msg  = ''; $referral_type = '';

/* ═══════════════════════════════════════════════════════════════════════
   MODULE PARRAINAGE — AUTO-INSTALL DES TABLES
═══════════════════════════════════════════════════════════════════════ */

/* referral_config */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_config` (
  `id`                    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `active`                TINYINT(1)       NOT NULL DEFAULT 1,
  `max_tokens_per_day`    SMALLINT         NOT NULL DEFAULT 5,
  `token_validity_days`   TINYINT          NOT NULL DEFAULT 30,
  `referee_discount_days` SMALLINT         NOT NULL DEFAULT 30,
  `sponsor_discount_days` SMALLINT         NOT NULL DEFAULT 90,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$webDB->query("INSERT IGNORE INTO `referral_config` (`id`) VALUES (1);");

/* referral_tokens — avec token_status ET referral_status séparés */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_tokens` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `sponsor_username` VARCHAR(64)   NOT NULL,
  `sponsor_email`    VARCHAR(255)  NOT NULL DEFAULT '',
  `sponsor_ip`       VARCHAR(45)   NOT NULL DEFAULT '',
  `token`            CHAR(8)       NOT NULL,
  `referee_username` VARCHAR(64)   DEFAULT NULL,
  `referee_email`    VARCHAR(255)  DEFAULT NULL,
  `referee_ip`       VARCHAR(45)   DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_at`          DATETIME      DEFAULT NULL,
  `expires_at`       DATETIME      NOT NULL,
  `validated_at`     DATETIME      DEFAULT NULL,
  `token_status`     ENUM('generated','used','expired') NOT NULL DEFAULT 'generated',
  `referral_status`  ENUM('pending','eligible','rewarded','expired') NOT NULL DEFAULT 'pending',
  `reward_given`     TINYINT(1)    NOT NULL DEFAULT 0,
  `reward_given_at`  DATETIME      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token`            (`token`),
  UNIQUE KEY `uq_referee_username` (`referee_username`),
  KEY `idx_sponsor`                (`sponsor_username`),
  KEY `idx_referral_status`        (`referral_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* referral_rewards */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_rewards` (
  `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target`        ENUM('sponsor','referee') NOT NULL DEFAULT 'sponsor',
  `nom`           VARCHAR(120)      NOT NULL,
  `description`   TEXT              DEFAULT NULL,
  `quantite`      SMALLINT          NOT NULL DEFAULT 1,
  `duree_en_jour` SMALLINT          NOT NULL DEFAULT 0,
  `code_boutique` VARCHAR(64)       DEFAULT NULL,
  `code_en_jeu`   VARCHAR(64)       DEFAULT NULL,
  `active`        TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`    TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* referral_discounts */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_discounts` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`        VARCHAR(64)   NOT NULL,
  `type`            ENUM('sponsor','referee') NOT NULL,
  `store_coupon_id` TINYINT UNSIGNED DEFAULT NULL
                    COMMENT '2=coupon parrain, 7=coupon filleul (ref referral_rewards.id)',
  `token_ref`       CHAR(8)       DEFAULT NULL,
  `starts_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`      DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_type` (`username`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* referral_reward_logs */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_reward_logs` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(64)   NOT NULL,
  `reward_type` VARCHAR(64)   NOT NULL,
  `reward_id`   SMALLINT UNSIGNED NOT NULL,
  `token`       CHAR(8)       NOT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_token`    (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* referral_ip_log */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_ip_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `username`   VARCHAR(64)  NOT NULL,
  `token_used` CHAR(8)      NOT NULL,
  `logged_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip`    (`ip`),
  KEY `idx_token` (`token_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* referral_ingame_codes — codes uniques filleul générés à l'activation */
$webDB->query("CREATE TABLE IF NOT EXISTS `referral_ingame_codes` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token_ref`        CHAR(8)       NOT NULL,
  `referee_username` VARCHAR(64)   NOT NULL,
  `reward_id`        SMALLINT UNSIGNED NOT NULL,
  `reward_nom`       VARCHAR(120)  NOT NULL DEFAULT '',
  `unique_code`      VARCHAR(32)   NOT NULL,
  `parent_code`      VARCHAR(255)  NOT NULL DEFAULT '',
  `redeemed`         TINYINT(1)    NOT NULL DEFAULT 0,
  `redeemed_at`      DATETIME      DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code`   (`unique_code`),
  KEY `idx_token`         (`token_ref`),
  KEY `idx_referee`       (`referee_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
/* Ajouter colonne target si absente */
$_tgt_col = $webDB->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='referral_ingame_codes' AND COLUMN_NAME='target'")->row()->cnt;
if (!(int)$_tgt_col) {
    $webDB->query("ALTER TABLE `referral_ingame_codes` ADD COLUMN `target` ENUM('sponsor','referee') NOT NULL DEFAULT 'referee' AFTER `parent_code`");
}

/* ── Lecture config ── */
$ref_cfg_row = $webDB->get('referral_config')->row();
$ref_active  = (int)($ref_cfg_row->active ?? 1);

/* ── Lecture récompenses (pour la popup info) ── */
$ref_rewards_sponsor = $webDB->where('target', 'sponsor')->where('active', 1)
                              ->order_by('sort_order', 'ASC')->get('referral_rewards')->result();
$ref_rewards_referee = $webDB->where('target', 'referee')->where('active', 1)
                              ->order_by('sort_order', 'ASC')->get('referral_rewards')->result();

/* ── Lecture reward id=2 et id=7 (coupons boutique pré-stockés) ── */
$reward_sponsor_coupon  = $webDB->where('id', 2)->get('referral_rewards')->row();
$reward_referee_coupon  = $webDB->where('id', 7)->get('referral_rewards')->row();

/* ── Expirer les tokens non utilisés ── */
$webDB->query("UPDATE `referral_tokens`
  SET `token_status` = 'expired', `referral_status` = 'expired'
  WHERE `token_status` = 'generated' AND `expires_at` < NOW();");

/* ── Tokens du parrain courant ── */
$my_tokens = $webDB->where('sponsor_username', $sess_username)
                    ->order_by('created_at', 'DESC')
                    ->get('referral_tokens')->result();
$ref_show_hist = !empty($my_tokens);

/* ── Vue filleul : mon parrainage ── */
$my_sponsor_token = $webDB->where('referee_username', $sess_username)
                           ->where_in('token_status', ['used', 'generated'])
                           ->order_by('used_at', 'DESC')
                           ->get('referral_tokens')->row();

$my_referee_disc = null;
if ($my_sponsor_token) {
    $my_referee_disc = $webDB->where('username', $sess_username)
                              ->where('token_ref', $my_sponsor_token->token)
                              ->where('type', 'referee')
                              ->get('referral_discounts')->row();
}

/* ── Codes uniques filleul (depuis referral_ingame_codes, target=referee) ── */
$referee_ingame_codes = [];
$codes_redeemed       = [];
if ($my_sponsor_token) {
    $referee_ingame_codes = $webDB
        ->where('referee_username', $sess_username)
        ->where('token_ref', $my_sponsor_token->token)
        ->where('target', 'referee')
        ->order_by('reward_id', 'ASC')
        ->get('referral_ingame_codes')->result();
    /* Statut d'utilisation via R1_Eluna.player_code_usage */
    foreach ($referee_ingame_codes as $_ric) {
        $codes_redeemed[$_ric->unique_code] =
            (bool)$elunaDB->where('code', $_ric->unique_code)->count_all_results('player_code_usage');
    }
}

/* ── Progression filleul globale (sidebar + carte) ── */
$fil_time_sec_glb = 0; $fil_lvl20_glb = 0; $fil_min_glb = 0;
if ($my_sponsor_token) {
    $_fil_acc_glb = $authDB->where('username', strtoupper($sess_username))->select('id')->get('account')->row();
    if ($_fil_acc_glb) {
        $_fil_chars_glb = $charDB->where('account', (int)$_fil_acc_glb->id)->select('level,totaltime')->get('characters')->result();
        foreach ($_fil_chars_glb as $_fc_glb) {
            $fil_time_sec_glb += (int)$_fc_glb->totaltime;
            if ((int)$_fc_glb->level >= 20) $fil_lvl20_glb++;
        }
    }
    $fil_min_glb = floor($fil_time_sec_glb / 60);
}

/* ── Tokens générés aujourd'hui ── */
$tokens_today = (int)$webDB->where('sponsor_username', $sess_username)
                             ->where('DATE(created_at)', date('Y-m-d'))
                             ->count_all_results('referral_tokens');

/* ── Email du compte courant ── */
$user_email_row = $webDB->select('email')->where('username', $sess_username)->get('users')->row();
$user_email     = $user_email_row->email ?? '';

/* ═══════════════════════════════
   ACTION POST : GÉNÉRATION TOKEN
═══════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['referral_action']) && $_POST['referral_action'] === 'generate') {

    $nonce_key = 'referral_nonce_' . $sess_id;

    if (empty($_POST['referral_nonce']) || $_POST['referral_nonce'] !== $this->session->userdata($nonce_key)) {
        $referral_msg = 'Requête invalide.'; $referral_type = 'error';
    } elseif (!$ref_active) {
        $referral_msg = 'Le module parrainage est désactivé.'; $referral_type = 'error';
    } elseif (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $referral_msg = 'Votre adresse e-mail est invalide. Mettez-la à jour dans vos paramètres.'; $referral_type = 'error';
    } elseif ($tokens_today >= (int)($ref_cfg_row->max_tokens_per_day ?? 5)) {
        $referral_msg = 'Limite journalière atteinte (' . $ref_cfg_row->max_tokens_per_day . ' tokens/jour). Revenez demain.'; $referral_type = 'error';
    } else {
        /* Génération token unique 8 chars */
        $chars     = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $new_token = '';
        $attempts  = 0;
        do {
            $new_token = '';
            for ($i = 0; $i < 8; $i++) $new_token .= $chars[random_int(0, strlen($chars) - 1)];
            $exists = $webDB->where('token', $new_token)->count_all_results('referral_tokens');
            $attempts++;
        } while ($exists > 0 && $attempts < 20);

        if ($exists > 0) {
            $referral_msg = 'Erreur de génération du token. Réessayez.'; $referral_type = 'error';
        } else {
            $expires    = date('Y-m-d H:i:s', strtotime('+' . ($ref_cfg_row->token_validity_days ?? 30) . ' days'));
            $sponsor_ip = $_SERVER['REMOTE_ADDR'] ?? '';

            $webDB->insert('referral_tokens', [
                'sponsor_username' => $sess_username,
                'sponsor_email'    => $user_email,
                'sponsor_ip'       => $sponsor_ip,
                'token'            => $new_token,
                'expires_at'       => $expires,
                'token_status'     => 'generated',
                'referral_status'  => 'pending',
            ]);

            $referral_msg  = 'Token <strong>' . $new_token . '</strong> généré ! Partagez-le à votre filleul. Valable jusqu\'au ' . date('d/m/Y', strtotime($expires)) . '.';
            $referral_type = 'success';

            /* ── Email de confirmation au parrain ── */
            if (!empty($user_email)) {
                $site_name = 'Celestia-WoW';
                $site_url  = base_url();
                $exp_label = date('d/m/Y', strtotime($expires));

                // Collecter les récompenses parrain pour l'email
                $rewards_list_html = '';
                foreach ($ref_rewards_sponsor as $rw) {
                    $rewards_list_html .= '<li>— ' . htmlspecialchars($rw->nom) . '</li>';
                }

                $mail_html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
                    . 'body{margin:0;padding:0;background:#06111c;font-family:Georgia,serif;color:#8da5bb;}'
                    . '.wrap{max-width:560px;margin:40px auto;background:#0a1a2c;border:1px solid rgba(200,168,75,.2);border-radius:10px;overflow:hidden;}'
                    . '.hd{background:linear-gradient(135deg,#0d1f36,#111e30);padding:28px 32px;border-bottom:1px solid rgba(200,168,75,.25);}'
                    . '.hd h1{margin:0;font-size:1.3rem;color:#c8a84b;letter-spacing:.1em;}'
                    . '.bd{padding:28px 32px;}'
                    . '.token-box{text-align:center;background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.3);border-radius:8px;padding:20px;margin:20px 0;}'
                    . '.token{font-family:Courier,monospace;font-size:2rem;letter-spacing:.35em;color:#e0c060;font-weight:bold;}'
                    . '.token-exp{font-size:.9rem;color:#6a8aaa;margin-top:8px;}'
                    . '.note{margin-top:14px;padding:10px 14px;background:rgba(93,190,122,.07);border:1px solid rgba(93,190,122,.2);border-radius:6px;font-size:.9rem;color:#7dd89a;}'
                    . '.rewards{list-style:none;margin:12px 0;padding:0;}'
                    . '.rewards li{margin:5px 0;font-size:.95rem;color:#8da5bb;}'
                    . '.ft{padding:14px 32px;background:rgba(0,0,0,.3);font-size:.82rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.06);}'
                    . '.ft a{color:#6a8aaa;text-decoration:none;}'
                    . '</style></head><body><div class="wrap">'
                    . '<div class="hd"><h1>&#127381; Votre token de parrainage</h1></div>'
                    . '<div class="bd">'
                    . '<p>Bonjour <strong style="color:#c8d4e0">' . htmlspecialchars($sess_username) . '</strong>,</p>'
                    . '<p>Votre token de parrainage a &eacute;t&eacute; g&eacute;n&eacute;r&eacute;. Partagez-le lors de l\'inscription d\'un ami.</p>'
                    . '<div class="token-box">'
                    . '<div class="token">' . htmlspecialchars($new_token) . '</div>'
                    . '<div class="token-exp">Valable jusqu\'au <strong style="color:#c8a84b">' . $exp_label . '</strong></div>'
                    . '</div>'
                    . '<p style="color:#6a8aaa;font-size:.9rem">R&eacute;compenses parrain d&egrave;s que votre filleul atteint <strong style="color:#c8d4e0">3h de jeu</strong> et <strong style="color:#c8d4e0">2 personnages niveau&nbsp;&ge;&nbsp;20</strong>&nbsp;:</p>'
                    . '<ul class="rewards">' . $rewards_list_html . '</ul>'
                    . '<div class="note">&#9432; Les r&eacute;compenses sont attribu&eacute;es automatiquement apr&egrave;s validation des conditions.</div>'
                    . '</div>'
                    . '<div class="ft"><a href="' . htmlspecialchars($site_url) . '">' . htmlspecialchars($site_name) . '</a>'
                    . ' &mdash; Email automatique.</div></div></body></html>';

                $this->load->library('email');
                $this->email->initialize(['mailtype' => 'html', 'charset' => 'utf-8']);
                $this->email->clear();
                $this->email->from('noreply@celestia-wow.com', $site_name);
                $this->email->to($user_email);
                $this->email->subject('[' . $site_name . '] Votre token de parrainage : ' . $new_token);
                $this->email->message($mail_html);
                $this->email->send();
                unset($mail_html);
            }

            /* Rafraîchir la liste */
            $my_tokens = $webDB->where('sponsor_username', $sess_username)->order_by('created_at', 'DESC')->get('referral_tokens')->result();
            $tokens_today++;
        }
    }
    $this->session->unset_userdata('referral_nonce_' . $sess_id);
}

/* ═══════════════════════════════
   ACTION POST : SUPPRESSION TOKEN
═══════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['referral_action']) && $_POST['referral_action'] === 'delete') {

    $nonce_key = 'referral_nonce_' . $sess_id;

    if (empty($_POST['referral_nonce']) || $_POST['referral_nonce'] !== $this->session->userdata($nonce_key)) {
        $referral_msg = 'Requête invalide.'; $referral_type = 'error';
    } else {
        $token_to_del = trim($_POST['delete_token'] ?? '');
        if (empty($token_to_del)) {
            $referral_msg = 'Token manquant.'; $referral_type = 'error';
        } else {
            $del_row = $webDB
                ->where('token', $token_to_del)
                ->where('sponsor_username', $sess_username)
                ->where_in('token_status', ['generated', 'expired'])
                ->limit(1)
                ->get('referral_tokens')
                ->row();
            if (!$del_row) {
                $referral_msg = 'Token introuvable ou non supprimable.'; $referral_type = 'error';
            } else {
                $webDB->where('token', $token_to_del)->where('sponsor_username', $sess_username)->delete('referral_tokens');
                $referral_msg = 'Token supprimé.'; $referral_type = 'success';
                $my_tokens = $webDB->where('sponsor_username', $sess_username)->order_by('created_at', 'DESC')->get('referral_tokens')->result();
            }
        }
    }
    $this->session->unset_userdata('referral_nonce_' . $sess_id);
}

/* Nonce parrainage */
$referral_nonce_val = bin2hex(random_bytes(16));
$this->session->set_userdata('referral_nonce_' . $sess_id, $referral_nonce_val);

/* ═══════════════════════════════
   ACTION : DÉBLOQUAGE
═══════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unstuck_char_name'])) {

    $nonce_key = 'unstuck_nonce_' . $sess_id;

    if (empty($_POST['unstuck_nonce']) || $_POST['unstuck_nonce'] !== $this->session->userdata($nonce_key)) {
        $unstuck_msg = 'Requête invalide. Veuillez réessayer.'; $unstuck_type = 'error';
    } else {
        $char_name = trim($this->db->escape_str($_POST['unstuck_char_name']));

        if (empty($char_name)) {
            $unstuck_msg = 'Aucun personnage sélectionné.'; $unstuck_type = 'error';
        } else {
            $q = $charDB->select('race')->from('characters')->where('name', $char_name)->get();

            if ($q->num_rows() === 0) {
                $unstuck_msg = 'Personnage introuvable.'; $unstuck_type = 'error';
            } else {
                $race     = (int)$q->row()->race;
                $alliance = [1, 3, 4, 7, 11];
                $horde    = [2, 5, 6, 8, 10];

                if (in_array($race, $alliance)) {
                    $px = -8949.95; $py = -132.493; $pz = 83.5312; $map = 0;
                } elseif (in_array($race, $horde)) {
                    $px = 1526.68; $py = -4420.85; $pz = 14.2085; $map = 1;
                } else {
                    $unstuck_msg = 'Race non reconnue.'; $unstuck_type = 'error';
                    goto unstuck_done;
                }

                $charDB->where('name', $char_name)->update('characters', [
                    'position_x' => $px, 'position_y' => $py,
                    'position_z' => $pz, 'map'         => $map,
                ]);

                if ($charDB->affected_rows() > 0) {
                    $unstuck_msg  = '<strong>' . htmlspecialchars($char_name) . '</strong> a été téléporté avec succès.';
                    $unstuck_type = 'success';
                } else {
                    $unstuck_msg  = 'Mise à jour échouée — personnage peut-être déjà à cet emplacement.';
                    $unstuck_type = 'error';
                }
            }
        }
        $this->session->unset_userdata($nonce_key);
    }
}
unstuck_done:

/* ═══════════════════════════════
   ACTION : TRANSFERT
═══════════════════════════════ */
$TRANSFER_COST = 50;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_char_name'])) {

    $nonce_key = 'transfer_nonce_' . $sess_id;

    if (empty($_POST['transfer_nonce']) || $_POST['transfer_nonce'] !== $this->session->userdata($nonce_key)) {
        $transfer_msg = 'Requête invalide. Veuillez réessayer.'; $transfer_type = 'error';
    } else {
        $char_name   = trim($this->db->escape_str($_POST['transfer_char_name']));
        $target_acct = trim($this->db->escape_str($_POST['transfer_target_account']));

        if (empty($char_name) || empty($target_acct)) {
            $transfer_msg = 'Veuillez remplir tous les champs.'; $transfer_type = 'error';
        } else {
            $authQ = $authDB->select('id')->from('account')->where('username', strtoupper($target_acct))->get();
            if ($authQ->num_rows() === 0) {
                $transfer_msg = 'Compte destinataire introuvable.'; $transfer_type = 'error';
                goto transfer_done;
            }
            $target_account_id = (int)$authQ->row()->id;

            $myAuthQ = $authDB->select('id')->from('account')->where('username', strtoupper($sess_username))->get();
            if ($myAuthQ->num_rows() === 0) {
                $transfer_msg = 'Impossible de vérifier votre compte.'; $transfer_type = 'error';
                goto transfer_done;
            }
            $my_account_id = (int)$myAuthQ->row()->id;

            $charOwnerQ = $charDB->select('guid')->from('characters')
                                  ->where('name', $char_name)->where('account', $my_account_id)->get();
            if ($charOwnerQ->num_rows() === 0) {
                $transfer_msg = 'Ce personnage ne vous appartient pas ou est introuvable.'; $transfer_type = 'error';
                goto transfer_done;
            }

            $countQ = $charDB->from('characters')->where('account', $target_account_id)->count_all_results();
            if ($countQ >= 10) {
                $transfer_msg = 'Le compte destinataire a atteint la limite de 10 personnages.'; $transfer_type = 'error';
                goto transfer_done;
            }

            $dpQ = $webDB->select('dp')->from('users')->where('username', $sess_username)->get();
            if ($dpQ->num_rows() === 0) {
                $transfer_msg = 'Impossible de vérifier votre solde DP.'; $transfer_type = 'error';
                goto transfer_done;
            }
            $current_dp = (int)$dpQ->row()->dp;

            if ($current_dp < $TRANSFER_COST) {
                $transfer_msg = 'Solde insuffisant. Le transfert coûte ' . $TRANSFER_COST . ' DP (solde : ' . $current_dp . ' DP).';
                $transfer_type = 'error';
                goto transfer_done;
            }

            $webDB->where('username', $sess_username)->update('users', ['dp' => $current_dp - $TRANSFER_COST]);
            $charDB->where('name', $char_name)->update('characters', ['account' => $target_account_id]);

            $transfer_msg  = '<strong>' . htmlspecialchars($char_name) . '</strong> transféré vers <strong>' . htmlspecialchars($target_acct) . '</strong>. ' . $TRANSFER_COST . ' DP déduits.';
            $transfer_type = 'success';
        }
        $this->session->unset_userdata($nonce_key);
    }
}
transfer_done:

/* ── Nonces formulaires ── */
$nonce_key_u   = 'unstuck_nonce_' . $sess_id;
$nonce_value_u = bin2hex(random_bytes(16));
$this->session->set_userdata($nonce_key_u, $nonce_value_u);

$nonce_key_t   = 'transfer_nonce_' . $sess_id;
$nonce_value_t = bin2hex(random_bytes(16));
$this->session->set_userdata($nonce_key_t, $nonce_value_t);

/* ── Collecte personnages + stats ── */
$all_chars     = [];
$total_seconds = 0;
$total_money   = 0;

foreach ($this->wowrealm->getRealms()->result() as $r) {
    $db  = $this->wowrealm->realmConnection($r->username, $r->password, $r->hostname, $r->char_database);
    $res = $this->wowrealm->getGeneralCharactersSpecifyAcc($db, $sess_id)->result();
    foreach ($res as $c) {
        $all_chars[]    = ['name' => $c->name, 'race' => $c->race ?? 0];
        $total_seconds += (int)($c->totaltime ?? 0);
        $total_money   += (int)($c->money     ?? 0);
    }
}

$tt_d = floor($total_seconds / 86400);
$tt_h = floor(($total_seconds % 86400) / 3600);
$tt_m = floor(($total_seconds % 3600)  / 60);
$total_time_str = $tt_d . 'j ' . $tt_h . 'h ' . $tt_m . 'm';

$tm_gold   = floor($total_money / 10000);
$tm_silver = floor(($total_money % 10000) / 100);
$tm_copper = $total_money % 100;

$dp_balance = 0;
$dpBal = $webDB->select('dp')->from('users')->where('username', $sess_username)->get();
if ($dpBal->num_rows()) $dp_balance = (int)$dpBal->row()->dp;

/* ══════════════════════════════════════════════════════════════════════
   HELPER : statut affiché parrainage
   Retourne ['label', 'badge_class', 'promo_code'|null, 'promo_exp'|null]
   pour chaque ligne de l'historique parrain
══════════════════════════════════════════════════════════════════════ */
function cwp_referral_row_data($tk, $webDB, $reward_sponsor_coupon, $coupon_duration_sponsor) {

    $referral_status = $tk->referral_status ?? 'pending';
    $token_status    = $tk->token_status    ?? 'generated';

    /* Statut badge */
    $badge_map = [
        'generated' => ['Actif',    'badge-pending'],
        'expired'   => ['Expiré',   'badge-expired'],
        'used'      => [
            'pending'  => ['En attente', 'badge-pending'],
            'eligible' => ['Éligible',   'badge-used'],
            'rewarded' => ['Récompensé', 'badge-rewarded'],
            'expired'  => ['Expiré',     'badge-expired'],
        ],
    ];

    if ($token_status === 'used') {
        [$label, $badge] = $badge_map['used'][$referral_status] ?? ['Utilisé', 'badge-used'];
    } elseif ($token_status === 'expired') {
        [$label, $badge] = $badge_map['expired'];
    } else {
        [$label, $badge] = $badge_map['generated'];
    }

    /* Code promo boutique parrain */
    $promo_code    = null;
    $promo_exp     = null;
    $promo_active  = false;
    $show_promo    = false;

    if ($referral_status === 'rewarded' || $referral_status === 'eligible') {
        /* Chercher dans referral_discounts */
        $disc = $webDB->where('username', $tk->sponsor_username ?? '')
                       ->where('token_ref', $tk->token)
                       ->where('type', 'sponsor')
                       ->get('referral_discounts')->row();

        if ($disc) {
            $unique_coupon = $disc->unique_code ?? null;
            $show_promo   = !empty($unique_coupon);
            $promo_code   = $unique_coupon;
            $promo_exp    = $disc->expires_at;
            $promo_active = $show_promo && strtotime($disc->expires_at) > time();
        }
    }

    return compact('label', 'badge', 'show_promo', 'promo_code', 'promo_exp', 'promo_active', 'referral_status');
}
?>

<link rel="stylesheet" href="<?= base_url('assets/css/panel.css?v='.time()); ?>">

<div class="cwp">
<div class="cwp__wrap">

  <aside class="cwp__aside">
    <nav class="cwp__nav">
      <div class="cwp__nav-hd"><i class="fas fa-compass"></i><span>Navigation</span></div>
      <div class="cwp__nav-group">
        <?php if ($this->wowmodule->getUCPStatus() == '1'): ?>
        <a href="<?= base_url('panel'); ?>" class="cwp__nav-link cwp__nav-link--active">
          <i class="fas fa-user-circle"></i><?= $this->lang->line('tab_account'); ?>
        </a>
        <?php endif; ?>
      </div>
      <div class="cwp__nav-group">
        <?php if ($this->wowmodule->getDonationStatus() == '1'): ?>
        <a href="<?= base_url('donate'); ?>" class="cwp__nav-link"><i class="fas fa-hand-holding-heart"></i><?= $this->lang->line('navbar_donate_panel'); ?></a>
        <?php endif; ?>
        <?php if ($this->wowmodule->getVoteStatus() == '1'): ?>
        <a href="<?= base_url('vote'); ?>" class="cwp__nav-link"><i class="fas fa-star"></i><?= $this->lang->line('navbar_vote_panel'); ?></a>
        <?php endif; ?>
        <?php if ($this->wowmodule->getStoreStatus() == '1'): ?>
        <a href="<?= base_url('store'); ?>" class="cwp__nav-link"><i class="fas fa-gem"></i><?= $this->lang->line('tab_store'); ?></a>
        <?php endif; ?>
      </div>
      <div class="cwp__nav-group">
        <?php if ($this->wowmodule->getBugtrackerStatus() == '1'): ?>
        <a href="<?= base_url('bugtracker'); ?>" class="cwp__nav-link"><i class="fas fa-bug"></i><?= $this->lang->line('tab_bugtracker'); ?></a>
        <?php endif; ?>
        <?php if ($this->wowmodule->getChangelogsStatus() == '1'): ?>
        <a href="<?= base_url('changelogs'); ?>" class="cwp__nav-link"><i class="fas fa-scroll"></i><?= $this->lang->line('tab_changelogs'); ?></a>
        <?php endif; ?>
        <?php if ($this->wowmodule->getDownloadStatus() == '1'): ?>
        <a href="<?= base_url('download'); ?>" class="cwp__nav-link"><i class="fas fa-download"></i><?= $this->lang->line('tab_download'); ?></a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="cwp-referral">
      <div class="cwp-referral__inner">
        <div class="cwp-referral__title"><i class="fas fa-gift"></i>Parrainage</div>
        <div class="cwp-referral__gifts">
          <div class="cwp-referral__gift"><i class="fas fa-horse"></i><span>Monture</span></div>
          <div class="cwp-referral__gift"><i class="fas fa-gem"></i><span>DP</span></div>
          <div class="cwp-referral__gift"><i class="fas fa-tag"></i><span>Coupon</span></div>
        </div>

        <?php if ($ref_active): ?>
        <div class="cwp-referral__stats">
          <div class="cwp-referral__stat">
            <span class="cwp-referral__stat-val"><?php
              $cnt_active = 0;
              foreach ((array)$my_tokens as $_t) { if ($_t->token_status === 'generated') $cnt_active++; }
              echo $cnt_active;
            ?></span>
            <span class="cwp-referral__stat-lbl">Actifs</span>
          </div>
          <div class="cwp-referral__stat-sep"></div>
          <div class="cwp-referral__stat">
            <span class="cwp-referral__stat-val"><?php
              $cnt_rewarded = 0;
              foreach ((array)$my_tokens as $_t) { if ($_t->referral_status === 'rewarded') $cnt_rewarded++; }
              echo $cnt_rewarded;
            ?></span>
            <span class="cwp-referral__stat-lbl">Validés</span>
          </div>
          <div class="cwp-referral__stat-sep"></div>
          <div class="cwp-referral__stat">
            <span class="cwp-referral__stat-val"><?= max(0, ($ref_cfg_row->max_tokens_per_day ?? 5) - $tokens_today); ?></span>
            <span class="cwp-referral__stat-lbl">Restants/j</span>
          </div>
        </div>
        <button class="cwp-referral__btn" onclick="cwpOpen('cwpReferralOverlay')">
          <i class="fas fa-user-plus"></i>Parrainer un ami
        </button>
        <?php else: ?>
        <button class="cwp-referral__btn cwp-referral__btn--disabled" disabled>
          <i class="fas fa-clock"></i>Bientôt disponible
        </button>
        <?php endif; ?>

        <?php if (!empty($referral_msg)): ?>
        <div class="cwp-referral__notif cwp-referral__notif--<?= $referral_type; ?>">
          <?= $referral_msg; ?>
        </div>
        <?php endif; ?>
        <?php if ($my_sponsor_token): ?>
        <div style="margin-top:.75rem;padding:.65rem .85rem;background:rgba(0,225,255,.05);border:1px solid rgba(0,225,255,.13);border-radius:6px;font-size:.82rem;line-height:1.6">
          <div style="color:rgba(0,225,255,.85);font-weight:600;margin-bottom:.3rem"><i class="fas fa-user-check" style="margin-right:.35rem"></i>Mon parrainage</div>
          <div style="color:var(--text-lo)">Filleul de <strong style="color:#00e1ff"><?= htmlspecialchars($my_sponsor_token->sponsor_username); ?></strong></div>
          <?php $rs_sb = $my_sponsor_token->referral_status ?? 'pending'; ?>
          <?php if ($rs_sb === 'rewarded'): ?>
          <div style="margin-top:.35rem;color:#7dd89a;font-size:.78rem"><i class="fas fa-check-circle" style="margin-right:.25rem"></i>Coupon boutique obtenu !</div>
          <?php elseif ($rs_sb === 'eligible'): ?>
          <div style="margin-top:.35rem;color:#c8a84b;font-size:.78rem"><i class="fas fa-clock" style="margin-right:.25rem"></i>Attribution en cours…</div>
          <?php else: ?>
          <div style="margin-top:.35rem;color:rgba(0,225,255,.55);font-size:.78rem"><i class="fas fa-hourglass-half" style="margin-right:.25rem"></i><?= $fil_min_glb; ?>min&nbsp;/&nbsp;180min &nbsp;·&nbsp; <?= $fil_lvl20_glb; ?>/2&nbsp;niv.20</div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div></aside><div class="cwp__main">

    <div class="cwp__section-head">
      <h1 class="cwp__section-title"><i class="fas fa-user-circle"></i><?= $this->lang->line('tab_account'); ?></h1>
      <div class="cwp__section-rule"></div>
    </div>

    <div class="cwp__card">
      <div class="cwp__card-hd">
        <div class="cwp__card-hd-l"><i class="fas fa-id-card"></i><h2><?= $this->lang->line('panel_account_details'); ?></h2></div>
        <a href="<?= base_url('settings'); ?>" class="cwp__btn"><i class="fas fa-user-edit"></i><?= $this->lang->line('button_account_settings'); ?></a>
      </div>
      <div class="cwp__card-bd">
        <table class="cwp__info">
          <tbody>
            <tr>
              <td class="cwp__info-lbl"><i class="fas fa-user"></i><?= $this->lang->line('placeholder_username'); ?></td>
              <td class="cwp__info-val"><?= $this->wowauth->getUsernameID($sess_id); ?></td>
            </tr>
            <tr>
              <td class="cwp__info-lbl"><i class="fas fa-envelope"></i><?= $this->lang->line('placeholder_email'); ?></td>
              <td class="cwp__info-val"><?= $this->wowauth->getEmailID($sess_id); ?></td>
            </tr>
            <tr>
              <td class="cwp__info-lbl"><i class="fas fa-map-marker-alt"></i><?= $this->lang->line('panel_last_ip'); ?></td>
              <td class="cwp__info-val"><?= $this->user_model->getLastIp($sess_id); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="cwp__card">
      <div class="cwp__card-hd">
        <div class="cwp__card-hd-l"><i class="fas fa-helmet-battle"></i><h2><?= $this->lang->line('panel_chars_list'); ?></h2></div>
        <div class="cwp__card-hd-r">
          <button class="cwp__btn cwp__btn--danger" onclick="cwpOpen('cwpUnstuckOverlay')">
            <i class="fas fa-life-ring"></i>Personnage bloqué
          </button>
          <button class="cwp__btn cwp__btn--warning" onclick="cwpOpen('cwpTransferOverlay')">
            <i class="fas fa-exchange-alt"></i>Transférer un personnage
          </button>
        </div>
      </div>

      <?php if (!empty($unstuck_msg)): ?>
      <div style="padding:.75rem 1.5rem 0">
        <div class="cwp__notif cwp__notif--<?= $unstuck_type; ?>">
          <i class="fas fa-<?= $unstuck_type === 'success' ? 'circle-check' : 'circle-xmark'; ?>"></i>
          <span><?= $unstuck_msg; ?></span>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($transfer_msg)): ?>
      <div style="padding:.75rem 1.5rem 0">
        <div class="cwp__notif cwp__notif--<?= $transfer_type; ?>">
          <i class="fas fa-<?= $transfer_type === 'success' ? 'circle-check' : 'circle-xmark'; ?>"></i>
          <span><?= $transfer_msg; ?></span>
        </div>
      </div>
      <?php endif; ?>

      <div class="cwp__card-bd">
        <?php foreach ($this->wowrealm->getRealms()->result() as $charsMultiRealm):
          $multiRealm = $this->wowrealm->realmConnection($charsMultiRealm->username, $charsMultiRealm->password, $charsMultiRealm->hostname, $charsMultiRealm->char_database);
          $charsList  = $this->wowrealm->getGeneralCharactersSpecifyAcc($multiRealm, $sess_id)->result();
        ?>
        <div class="cwp__realm">
          <div class="cwp__realm-hd">
            <i class="fas fa-server"></i>
            <span><?= $this->wowrealm->getRealmName($charsMultiRealm->realmID); ?></span>
          </div>
          <?php if (count($charsList) > 0): ?>
          <div class="cwp__chars-wrap">
            <table class="cwp__chars-table">
              <thead>
                <tr>
                  <th><span class="th-icon"><i class="fas fa-user"></i></span>Nom</th>
                  <th><span class="th-icon"><i class="fas fa-shield-alt"></i></span>Race / Classe</th>
                  <th><span class="th-icon"><i class="fas fa-arrow-up"></i></span>Niveau</th>
                  <th><span class="th-icon"><i class="fas fa-clock"></i></span>Temps de jeu</th>
                  <th><span class="th-icon"><i class="fas fa-coins"></i></span>Monnaie</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($charsList as $chars): ?>
                <tr>
                  <td><span class="cwp__char-name"><?= $chars->name; ?></span></td>
                  <td>
                    <div class="cwp__char-icons">
                      <div class="cwp__char-icon-wrap" data-tip="<?= $this->wowgeneral->getRaceName($chars->race); ?>">
                        <img class="cwp__char-icon" src="<?= base_url('assets/images/races/' . $this->wowgeneral->getRaceIcon($chars->race)); ?>" alt="<?= $this->wowgeneral->getRaceName($chars->race); ?>">
                      </div>
                      <div class="cwp__char-icon-wrap" data-tip="<?= $this->wowgeneral->getClassName($chars->class); ?>">
                        <img class="cwp__char-icon" src="<?= base_url('assets/images/class/' . $this->wowgeneral->getClassIcon($chars->class)); ?>" alt="<?= $this->wowgeneral->getClassName($chars->class); ?>">
                      </div>
                    </div>
                  </td>
                  <td><span class="cwp__char-level"><?= $chars->level; ?></span></td>
                  <td><span class="cwp__char-time"><?= $this->wowgeneral->timeConversor($chars->totaltime); ?></span></td>
                  <td>
                    <span class="cwp__char-money">
                      <span class="gold-coin"><?= $this->wowgeneral->moneyConversor($chars->money)['gold']; ?>g</span>
                      <span class="silver-coin"><?= $this->wowgeneral->moneyConversor($chars->money)['silver']; ?>s</span>
                      <span class="copper-coin"><?= $this->wowgeneral->moneyConversor($chars->money)['copper']; ?>c</span>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="cwp__chars-empty"><i class="fas fa-ghost"></i>Aucun personnage sur ce royaume.</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="cwp__chars-footer">
          <div class="cwp__total-time">
            <i class="fas fa-hourglass-half"></i>
            <span>Temps de jeu total&nbsp;:<strong><?= $total_time_str; ?></strong></span>
          </div>
          <div class="cwp__total-time cwp__total-money">
            <i class="fas fa-coins" style="color:#00e1ff"></i>
            <span>Monnaie totale&nbsp;:
              <strong style="color:#00e1ff"><?= number_format($tm_gold, 0, ',', ' '); ?>g</strong>
              <strong style="color:#aaa"><?= str_pad($tm_silver, 2, '0', STR_PAD_LEFT); ?>s</strong>
              <strong style="color:#c87540"><?= str_pad($tm_copper, 2, '0', STR_PAD_LEFT); ?>c</strong>
            </span>
          </div>
        </div>
      </div></div><?php if (!empty($my_sponsor_token)): ?>
    <div class="cwp__card cwp__card--referral-hist" id="cwpMyReferral">
      <div class="cwp__card-hd">
        <div class="cwp__card-hd-l">
          <i class="fas fa-user-check" style="color:#00e1ff"></i>
          <h2 style="color:#fff">Mon parrainage</h2>
        </div>
        <span class="cwp-dp-badge"><i class="fas fa-heart"></i>Filleul de <?= htmlspecialchars($my_sponsor_token->sponsor_username); ?></span>
      </div>
      <div class="cwp__card-bd">

        <!-- Résumé : token, parrain, date, statut -->
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;padding:.25rem 0 1.1rem;border-bottom:1px solid rgba(255,255,255,.07);margin-bottom:1.2rem">
          <div>
            <span style="font-size:.78rem;color:var(--text-lo);display:block;margin-bottom:.2rem">Token utilisé</span>
            <span class="cwp-hist-token" onclick="cwpCopyToken('<?= htmlspecialchars($my_sponsor_token->token); ?>')" title="Copier" style="cursor:pointer">
              <?= htmlspecialchars($my_sponsor_token->token); ?><i class="fas fa-copy" style="font-size:.85rem;margin-left:.35rem;color:rgba(0,225,255,.4)"></i>
            </span>
          </div>
          <div>
            <span style="font-size:.78rem;color:var(--text-lo);display:block;margin-bottom:.2rem">Parrain</span>
            <span class="cwp-hist-referee"><?= htmlspecialchars($my_sponsor_token->sponsor_username); ?></span>
          </div>
          <?php if ($my_sponsor_token->used_at): ?>
          <div>
            <span style="font-size:.78rem;color:var(--text-lo);display:block;margin-bottom:.2rem">Activé le</span>
            <span style="color:var(--text-mid)"><?= date('d/m/Y', strtotime($my_sponsor_token->used_at)); ?></span>
          </div>
          <?php endif; ?>
          <div style="margin-left:auto">
            <?php
            $ref_rs_c = $my_sponsor_token->referral_status ?? 'pending';
            $badges_c = ['pending'=>['En attente','badge-pending'],'eligible'=>['Éligible','badge-used'],'rewarded'=>['Récompensé','badge-rewarded'],'expired'=>['Expiré','badge-expired']];
            [$slbl_c, $sbdg_c] = $badges_c[$ref_rs_c] ?? ['Inconnu','badge-expired'];
            ?>
            <span class="cwp-ref-token-badge <?= $sbdg_c; ?>"><?= $slbl_c; ?></span>
          </div>
        </div>

        <?php if (!empty($referee_ingame_codes)): ?>
        <!-- Codes uniques en jeu (générés à l'activation) -->
        <div style="background:rgba(93,190,122,.06);border:1px solid rgba(93,190,122,.2);border-radius:8px;padding:1rem 1.25rem;margin-bottom:1.2rem">
          <div style="font-size:.8rem;color:#5dbe7a;letter-spacing:.09em;text-transform:uppercase;margin-bottom:.75rem;font-weight:600">
            <i class="fas fa-gift" style="margin-right:.4rem"></i>Codes en jeu personnels — à usage unique
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:.7rem">
            <?php foreach ($referee_ingame_codes as $_igc):
              $_redeemed = !empty($codes_redeemed[$_igc->unique_code]);
            ?>
            <div style="background:rgba(93,190,122,.<?= $_redeemed ? '04' : '10'; ?>);border:1px solid rgba(93,190,122,.<?= $_redeemed ? '12' : '25'; ?>);border-radius:6px;padding:.65rem 1rem;text-align:center;min-width:160px;flex:1;position:relative">
              <?php if ($_redeemed): ?>
              <span style="position:absolute;top:.35rem;right:.5rem;font-size:.7rem;color:#7dd89a;background:rgba(93,190,122,.15);padding:.1rem .4rem;border-radius:3px;letter-spacing:.05em">Utilisé ✓</span>
              <?php else: ?>
              <span style="position:absolute;top:.35rem;right:.5rem;font-size:.7rem;color:rgba(0,225,255,.7);background:rgba(0,225,255,.08);padding:.1rem .4rem;border-radius:3px;letter-spacing:.05em">Disponible</span>
              <?php endif; ?>
              <?php if (!$_redeemed): ?>
              <span onclick="cwpCopyToken('<?= htmlspecialchars($_igc->unique_code); ?>')" title="Copier le code" style="cursor:pointer;display:block;font-family:Courier,monospace;font-size:1.05rem;letter-spacing:.2em;font-weight:bold;color:#7dd89a;margin-bottom:.2rem;margin-top:.9rem">
                <?= htmlspecialchars($_igc->unique_code); ?><i class="fas fa-copy" style="font-size:.8rem;margin-left:.3rem;color:rgba(93,190,122,.5)"></i>
              </span>
              <?php else: ?>
              <span style="display:block;font-family:Courier,monospace;font-size:1.05rem;letter-spacing:.2em;font-weight:bold;color:rgba(93,190,122,.35);text-decoration:line-through;margin-bottom:.2rem;margin-top:.9rem">
                <?= htmlspecialchars($_igc->unique_code); ?>
              </span>
              <?php endif; ?>
              <span style="font-size:.77rem;color:#4a8a5a;font-style:italic"><?= htmlspecialchars($_igc->reward_nom); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:.75rem;font-size:.82rem;color:#4a7a5a">
            <i class="fas fa-info-circle" style="margin-right:.3rem"></i>Codes <strong style="color:#5dbe7a">personnels et à usage unique</strong>. Rendez-vous auprès du NPC <strong style="color:#7dd89a">Tyraël</strong> en jeu.
          </div>
        </div>
        <?php endif; ?>

        <!-- Prochaine récompense : Coupon boutique -->
        <?php
        $ref_rs_c2 = $my_sponsor_token->referral_status ?? 'pending';
        if ($ref_rs_c2 === 'rewarded' || $ref_rs_c2 === 'eligible'):
            $_coup_f = $reward_referee_coupon;
            $_disc_f = $my_referee_disc;
            $_actif  = $_disc_f && strtotime($_disc_f->expires_at) > time();
        ?>
        <div style="background:rgba(200,168,75,.06);border:1px solid rgba(200,168,75,.22);border-radius:8px;padding:1rem 1.25rem">
          <div style="font-size:.8rem;color:#c8a84b;letter-spacing:.09em;text-transform:uppercase;margin-bottom:.75rem;font-weight:600">
            <i class="fas fa-tag" style="margin-right:.4rem"></i>Coupon boutique débloqué — 20% de réduction
          </div>
          <?php if ($_disc_f && !empty($_disc_f->unique_code)): ?>
          <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <span class="cwp-hist-promo<?= $_actif ? ' cwp-hist-promo--active' : ''; ?>">
              <span onclick="cwpCopyToken('<?= htmlspecialchars($_disc_f->unique_code); ?>')" title="Copier le code boutique" style="cursor:pointer">
                <strong style="font-family:Courier,monospace;letter-spacing:.1em;font-size:1.1rem;color:#00e1ff"><?= htmlspecialchars($_disc_f->unique_code); ?></strong>
                <i class="fas fa-copy" style="font-size:.8rem;margin-left:.3rem;color:rgba(0,225,255,.35)"></i>
              </span>
              <?php if ($_actif): ?><span class="cwp-hist-promo-badge">Actif</span><?php else: ?><span style="color:var(--text-lo);font-size:.8rem">expiré</span><?php endif; ?>
            </span>
            <?php if ($_disc_f && $_disc_f->expires_at): ?>
            <span style="font-size:.85rem;color:var(--text-lo)">
              Valable jusqu'au <span style="color:<?= $_actif ? 'var(--text-mid)' : 'var(--text-lo)'; ?>"><?= date('d/m/Y', strtotime($_disc_f->expires_at)); ?></span>
            </span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php else:
        /* Pas encore éligible — afficher la progression */
        $_cond_t = $fil_time_sec_glb >= 10800;
        $_cond_l = $fil_lvl20_glb >= 2;
        $_pct_t  = min(100, round(($fil_time_sec_glb / 10800) * 100));
        $_pct_l  = min(100, round(($fil_lvl20_glb / 2) * 100));
        ?>
        <div style="background:rgba(0,225,255,.04);border:1px solid rgba(0,225,255,.12);border-radius:8px;padding:1rem 1.25rem">
          <div style="font-size:.8rem;color:rgba(0,225,255,.75);letter-spacing:.09em;text-transform:uppercase;margin-bottom:.75rem;font-weight:600">
            <i class="fas fa-tag" style="margin-right:.4rem"></i>À débloquer : Coupon boutique 20%
          </div>
          <div style="font-size:.87rem;color:var(--text-lo);margin-bottom:.9rem">Remplissez ces 2 conditions pour déverrouiller votre coupon boutique.</div>

          <!-- Condition 1 : temps de jeu -->
          <div style="margin-bottom:.8rem">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
              <span style="font-size:.88rem;color:<?= $_cond_t ? '#7dd89a' : 'var(--text-mid)'; ?>">
                <?php if ($_cond_t): ?><i class="fas fa-check-circle" style="margin-right:.4rem;color:#7dd89a"></i><?php else: ?><i class="fas fa-hourglass-half" style="margin-right:.4rem"></i><?php endif; ?>Temps de jeu total
              </span>
              <span style="font-size:.85rem;color:<?= $_cond_t ? '#7dd89a' : 'var(--text-lo)'; ?>"><?= $fil_min_glb; ?>&nbsp;min / 180&nbsp;min</span>
            </div>
            <div style="background:rgba(255,255,255,.07);border-radius:4px;height:6px;overflow:hidden">
              <div style="width:<?= $_pct_t; ?>%;height:100%;background:<?= $_cond_t ? 'linear-gradient(90deg,#5dbe7a,#7dd89a)' : 'linear-gradient(90deg,#006680,#00e1ff)'; ?>;border-radius:4px;transition:width .4s"></div>
            </div>
          </div>

          <!-- Condition 2 : personnages niv.20 -->
          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
              <span style="font-size:.88rem;color:<?= $_cond_l ? '#7dd89a' : 'var(--text-mid)'; ?>">
                <?php if ($_cond_l): ?><i class="fas fa-check-circle" style="margin-right:.4rem;color:#7dd89a"></i><?php else: ?><i class="fas fa-user-shield" style="margin-right:.4rem"></i><?php endif; ?>Personnages niveau ≥ 20
              </span>
              <span style="font-size:.85rem;color:<?= $_cond_l ? '#7dd89a' : 'var(--text-lo)'; ?>"><?= $fil_lvl20_glb; ?> / 2</span>
            </div>
            <div style="background:rgba(255,255,255,.07);border-radius:4px;height:6px;overflow:hidden">
              <div style="width:<?= $_pct_l; ?>%;height:100%;background:<?= $_cond_l ? 'linear-gradient(90deg,#5dbe7a,#7dd89a)' : 'linear-gradient(90deg,#006680,#00e1ff)'; ?>;border-radius:4px;transition:width .4s"></div>
            </div>
          </div>

          <?php if ($_cond_t && $_cond_l): ?>
          <div style="margin-top:.9rem;padding:.6rem .9rem;background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.2);border-radius:6px;font-size:.88rem;color:#c8a84b">
            <i class="fas fa-check-double" style="margin-right:.4rem"></i>Conditions atteintes ! Votre coupon sera attribué automatiquement sous peu.
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>


    <?php if ($ref_show_hist): ?>
    <div class="cwp__card cwp__card--referral-hist" id="cwpReferralHistory">
      <div class="cwp__card-hd">
        <div class="cwp__card-hd-l">
          <i class="fas fa-th-list" style="color:#00e1ff"></i>
          <h2 style="color:#fff">Historique de parrainage</h2>
        </div>
        <span class="cwp-dp-badge"><i class="fas fa-key"></i><?= count($my_tokens); ?> token(s)</span>
      </div>
      <div class="cwp__card-bd" style="padding:0">
        <div class="cwp__chars-wrap">
          <table class="cwp__chars-table cwp-hist-table">
            <thead>
              <tr>
                <th><span class="th-icon"><i class="fas fa-key"></i></span>Token</th>
                <th><span class="th-icon"><i class="fas fa-user-plus"></i></span>Filleul</th>
                <th><span class="th-icon"><i class="fas fa-calendar-check"></i></span>Utilisation</th>
                <th><span class="th-icon"><i class="fas fa-hourglass-end"></i></span>Exp. token</th>
                <th><span class="th-icon"><i class="fas fa-tag"></i></span>Coupon boutique</th>
                <th><span class="th-icon"><i class="fas fa-horse"></i></span>Monture</th>
                <th><span class="th-icon"><i class="fas fa-calendar-times"></i></span>Exp. coupon</th>
                <th><span class="th-icon"><i class="fas fa-dot-circle"></i></span>Statut</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($my_tokens as $tk):
                // Enrichir avec sponsor_username pour le helper
                $tk->sponsor_username = $sess_username;
                $row_data = cwp_referral_row_data($tk, $webDB, $reward_sponsor_coupon, $coupon_duration_sponsor ?? 90);

                // Calculer progression filleul si pending
                $fil_progress = null;
                if ($tk->token_status === 'used' && in_array($tk->referral_status, ['pending','eligible']) && $tk->referee_username) {
                    $fil_acc2 = $authDB->where('username', strtoupper($tk->referee_username))->select('id')->get('account')->row();
                    if ($fil_acc2) {
                        $fil_chars2 = $charDB->where('account', (int)$fil_acc2->id)->select('level,totaltime')->get('characters')->result();
                        $f2_time = 0; $f2_lvl20 = 0;
                        foreach ($fil_chars2 as $_fc2) { $f2_time += (int)$_fc2->totaltime; if ((int)$_fc2->level >= 20) $f2_lvl20++; }
                        $fil_progress = ['min' => floor($f2_time/60), 'lvl20' => $f2_lvl20, 'pct' => min(100, round(($f2_time/10800)*100))];
                    }
                }

                // Coupon parrain depuis referral_discounts
                $disc_sp = $webDB->where('username', $sess_username)->where('token_ref', $tk->token)->where('type', 'sponsor')->get('referral_discounts')->row();
                /* Code monture unique parrain depuis referral_ingame_codes */
                $_smc = $webDB->where('referee_username', $sess_username)->where('token_ref', $tk->token)->where('target', 'sponsor')->limit(1)->get('referral_ingame_codes')->row();
                $sp_mount_code     = $_smc ?: null;
                $sp_mount_redeemed = $_smc ? (bool)$elunaDB->where('code', $_smc->unique_code)->count_all_results('player_code_usage') : false;
              ?>
              <tr class="cwp-hist-row cwp-hist-row--<?= $tk->token_status; ?>">
                <td>
                  <span class="cwp-hist-token" onclick="cwpCopyToken('<?= htmlspecialchars($tk->token); ?>')" title="Copier">
                    <?= htmlspecialchars($tk->token); ?>
                    <i class="fas fa-copy" style="font-size:.9rem;margin-left:.4rem;color:rgba(0,225,255,.4)"></i>
                  </span>
                </td>
                <td>
                  <?php if ($tk->referee_username): ?>
                    <span class="cwp-hist-referee"><?= htmlspecialchars($tk->referee_username); ?></span>
                  <?php else: ?>
                    <span style="color:var(--text-lo);font-style:italic">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($tk->used_at): ?>
                    <span style="color:var(--text-mid)"><?= date('d/m/Y', strtotime($tk->used_at)); ?></span>
                  <?php else: ?>
                    <span style="color:var(--text-lo);font-style:italic">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($tk->token_status === 'generated'): ?>
                    <?php $tok_exp = strtotime($tk->expires_at) < time(); ?>
                    <span style="color:<?= $tok_exp ? 'var(--text-lo)' : 'var(--text-mid)'; ?>">
                      <?= date('d/m/Y', strtotime($tk->expires_at)); ?>
                    </span>
                  <?php else: ?>
                    <span style="color:var(--text-lo);font-style:italic">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($row_data['show_promo'] && $row_data['promo_code']): ?>
                    <span class="cwp-hist-promo<?= $row_data['promo_active'] ? ' cwp-hist-promo--active' : ''; ?>">
                      <span onclick="cwpCopyToken('<?= htmlspecialchars($row_data['promo_code']); ?>')" title="Copier le code boutique" style="cursor:pointer">
                        <strong style="font-family:Courier,monospace;letter-spacing:.1em;font-size:1rem;color:#00e1ff"><?= htmlspecialchars($row_data['promo_code']); ?></strong>
                        <i class="fas fa-copy" style="font-size:.78rem;margin-left:.3rem;color:rgba(0,225,255,.35)"></i>
                      </span>
                      <?php if ($row_data['promo_active']): ?>
                        <span class="cwp-hist-promo-badge">Actif</span>
                      <?php else: ?>
                        <span style="color:var(--text-lo);font-size:.8rem">expir&eacute;</span>
                      <?php endif; ?>
                    </span>
                  <?php elseif ($tk->token_status === 'used' && in_array($tk->referral_status, ['pending','eligible'])): ?>
                    <?php if ($fil_progress): ?>
                      <span style="color:var(--text-lo);font-style:italic;font-size:.88rem">
                        <i class="fas fa-hourglass-half" style="margin-right:.2rem"></i>
                        <?= $fil_progress['min']; ?>min / 180min
                        &nbsp;|&nbsp;
                        <i class="fas fa-user-shield" style="margin-right:.2rem"></i>
                        <?= $fil_progress['lvl20']; ?>/2 niv.20
                      </span>
                      <div class="cwp-cond-bar">
                        <div class="cwp-cond-track"><div class="cwp-cond-fill" style="width:<?= $fil_progress['pct']; ?>%"></div></div>
                      </div>
                    <?php else: ?>
                      <span style="color:var(--text-lo);font-style:italic;font-size:.88rem">En attente de validation</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color:var(--text-lo);font-style:italic">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($sp_mount_code): ?>
                    <?php if (!$sp_mount_redeemed): ?>
                    <span class="cwp-hist-token" onclick="cwpCopyToken('<?= htmlspecialchars($sp_mount_code->unique_code); ?>')" title="Copier" style="cursor:pointer">
                      <?= htmlspecialchars($sp_mount_code->unique_code); ?><i class="fas fa-copy" style="font-size:.85rem;margin-left:.35rem;color:rgba(0,225,255,.4)"></i>
                    </span>
                    <span style="display:block;font-size:.74rem;color:rgba(0,225,255,.6);margin-top:.15rem">Disponible</span>
                    <?php else: ?>
                    <span style="font-family:Courier,monospace;font-size:.9rem;color:rgba(0,225,255,.3);text-decoration:line-through">
                      <?= htmlspecialchars($sp_mount_code->unique_code); ?>
                    </span>
                    <span style="display:block;font-size:.74rem;color:#7dd89a;margin-top:.15rem"><i class="fas fa-check" style="margin-right:.2rem"></i>Utilisé</span>
                    <?php endif; ?>
                  <?php elseif ($tk->token_status === 'used' && in_array($tk->referral_status, ['rewarded','eligible'])): ?>
                    <span style="color:var(--text-lo);font-style:italic;font-size:.88rem">—</span>
                  <?php else: ?>
                    <span style="color:var(--text-lo);font-style:italic;font-size:.88rem">En attente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($disc_sp && $disc_sp->expires_at): ?>
                    <?php $pe = strtotime($disc_sp->expires_at) > time(); ?>
                    <span style="color:<?= $pe ? 'var(--text-mid)' : 'var(--text-lo)'; ?>">
                      <?= date('d/m/Y', strtotime($disc_sp->expires_at)); ?>
                    </span>
                  <?php else: ?>
                    <span style="color:var(--text-lo);font-style:italic">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="cwp-ref-token-badge <?= $row_data['badge']; ?>"><?= $row_data['label']; ?></span>
                </td>
                <td style="text-align:center;white-space:nowrap">
                  <?php if (in_array($tk->token_status, ['generated', 'expired'])): ?>
                  <form method="post" action="" style="display:inline" onsubmit="return confirm('Supprimer ce token de parrainage ?')">
                    <input type="hidden" name="referral_action" value="delete">
                    <input type="hidden" name="delete_token" value="<?= htmlspecialchars($tk->token); ?>">
                    <input type="hidden" name="referral_nonce" value="<?= htmlspecialchars($referral_nonce_val); ?>">
                    <button type="submit" title="Supprimer" style="background:none;border:none;cursor:pointer;padding:4px 6px;color:rgba(255,80,80,.55);transition:color .15s" onmouseover="this.style.color='rgba(255,80,80,1)'" onmouseout="this.style.color='rgba(255,80,80,.55)'">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div></div></div><div class="cwp-overlay" id="cwpUnstuckOverlay" role="dialog" aria-modal="true">
  <div class="cwp-modal cwp-modal--danger">
    <div class="cwp-modal__hd">
      <div class="cwp-modal__hd-l">
        <i class="fas fa-life-ring" style="color:var(--danger-c)"></i>
        <h3>Personnage bloqué</h3>
      </div>
      <button class="cwp-modal__close" onclick="cwpClose('cwpUnstuckOverlay')"><i class="fas fa-times"></i></button>
    </div>
    <div class="cwp-modal__bd">
      <div class="cwp-modal__alert cwp-modal__alert--warn">
        <i class="fas fa-exclamation-triangle"></i>
        <p><strong>Important —</strong> Quittez le jeu avant de valider. Votre personnage sera téléporté au point de rassemblement de faction.</p>
      </div>
      <div class="cwp-modal__field">
        <label for="cwpUnstuckSelect"><i class="fas fa-user"></i>Choisir un personnage</label>
        <?php if (empty($all_chars)): ?>
          <p style="font-family:var(--font-body);font-size:1.05rem;font-style:italic;color:#94a3b8">Aucun personnage trouvé.</p>
        <?php else: ?>
        <select class="cwp-modal__select" id="cwpUnstuckSelect">
          <option value="">— Sélectionner —</option>
          <?php foreach ($all_chars as $c): ?>
          <option value="<?= htmlspecialchars($c['name']); ?>"><?= htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
      </div>
    </div>
    <div class="cwp-modal__ft">
      <button class="cwp-modal__cancel" onclick="cwpClose('cwpUnstuckOverlay')"><i class="fas fa-times"></i>Annuler</button>
      <?php if (!empty($all_chars)): ?>
      <button class="cwp-modal__submit cwp-modal__submit--danger" onclick="cwpSubmitUnstuck()"><i class="fas fa-check"></i>Valider le débloquage</button>
      <?php endif; ?>
    </div>
  </div>
</div>
<form id="cwpUnstuckForm" method="POST" action="<?= current_url(); ?>" style="display:none">
  <input type="hidden" name="unstuck_char_name" id="cwpUnstuckInput">
  <input type="hidden" name="unstuck_nonce"     value="<?= $nonce_value_u; ?>">
</form>


<div class="cwp-overlay" id="cwpTransferOverlay" role="dialog" aria-modal="true">
  <div class="cwp-modal cwp-modal--warning">
    <div class="cwp-modal__hd">
      <div class="cwp-modal__hd-l">
        <i class="fas fa-exchange-alt" style="color:var(--warning-c)"></i>
        <h3>Transférer un personnage</h3>
      </div>
      <button class="cwp-modal__close" onclick="cwpClose('cwpTransferOverlay')"><i class="fas fa-times"></i></button>
    </div>
    <div class="cwp-modal__bd">
      <div class="cwp-modal__alert cwp-modal__alert--info">
        <i class="fas fa-info-circle"></i>
        <p>Le transfert coûte <strong><?= $TRANSFER_COST; ?> DP</strong>.<br>
        Votre solde : <span class="cwp-dp-badge"><i class="fas fa-gem"></i><?= $dp_balance; ?> DP</span>
        <?php if ($dp_balance < $TRANSFER_COST): ?><br><span style="color:#ff4d4d;font-size:1rem">⚠ Solde insuffisant.</span><?php endif; ?>
        </p>
      </div>
      <div class="cwp-modal__alert cwp-modal__alert--warn">
        <i class="fas fa-exclamation-triangle"></i>
        <p><strong>Attention —</strong> Action <strong>irréversible</strong>. Le compte destinataire doit avoir moins de 10 personnages.</p>
      </div>
      <div class="cwp-modal__field">
        <label for="cwpTransferSelect"><i class="fas fa-user"></i>Personnage à transférer</label>
        <?php if (empty($all_chars)): ?>
          <p style="font-family:var(--font-body);font-size:1.05rem;font-style:italic;color:#94a3b8">Aucun personnage trouvé.</p>
        <?php else: ?>
        <select class="cwp-modal__select" id="cwpTransferSelect">
          <option value="">— Sélectionner —</option>
          <?php foreach ($all_chars as $c): ?>
          <option value="<?= htmlspecialchars($c['name']); ?>"><?= htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
      </div>
      <div class="cwp-modal__field">
        <label for="cwpTransferAccount"><i class="fas fa-at"></i>Compte destinataire</label>
        <input class="cwp-modal__input" type="text" id="cwpTransferAccount"
               placeholder="Nom du compte WoW" autocomplete="off" spellcheck="false">
      </div>
    </div>
    <div class="cwp-modal__ft">
      <button class="cwp-modal__cancel" onclick="cwpClose('cwpTransferOverlay')"><i class="fas fa-times"></i>Annuler</button>
      <?php if (!empty($all_chars) && $dp_balance >= $TRANSFER_COST): ?>
      <button class="cwp-modal__submit cwp-modal__submit--warning" onclick="cwpSubmitTransfer()">
        <i class="fas fa-check"></i>Confirmer (<?= $TRANSFER_COST; ?> DP)
      </button>
      <?php else: ?>
      <button class="cwp-modal__submit cwp-modal__submit--warning" disabled style="opacity:.4;cursor:not-allowed">
        <i class="fas fa-ban"></i>Solde insuffisant
      </button>
      <?php endif; ?>
    </div>
  </div>
</div>
<form id="cwpTransferForm" method="POST" action="<?= current_url(); ?>" style="display:none">
  <input type="hidden" name="transfer_char_name"      id="cwpTransferCharInput">
  <input type="hidden" name="transfer_target_account" id="cwpTransferAccountInput">
  <input type="hidden" name="transfer_nonce"          value="<?= $nonce_value_t; ?>">
</form>



<div class="cwp-overlay" id="cwpReferralOverlay" role="dialog" aria-modal="true">
  <div class="cwp-modal cwp-modal--ice cwp-modal--ref" style="max-width:860px;">

    <!-- Header -->
    <div class="cwp-modal__hd cwp-modal__hd--ref">
      <div class="cwp-modal__hd-l">
        <div class="cwp-ref-hd-icon"><i class="fas fa-gift"></i></div>
        <div>
          <h3>Programme de Parrainage</h3>
          <p class="cwp-ref-hd-sub">Invitez vos amis &amp; gagnez des r&eacute;compenses exclusives</p>
        </div>
      </div>
      <button class="cwp-modal__close" onclick="cwpClose('cwpReferralOverlay')"><i class="fas fa-times"></i></button>
    </div>

    <!-- Tabs -->
    <div class="cwp-ref-tabs">
      <button class="cwp-ref-tab cwp-ref-tab--gold is-active" data-tab="Parrain" onclick="cwpRefTab('Parrain')">
        <i class="fas fa-crown"></i>Je parraine
      </button>
      <button class="cwp-ref-tab cwp-ref-tab--emerald" data-tab="Filleul" onclick="cwpRefTab('Filleul')">
        <i class="fas fa-user-plus"></i>Je suis filleul
      </button>
      <button class="cwp-ref-tab cwp-ref-tab--cyan" data-tab="Tokens" onclick="cwpRefTab('Tokens')">
        <i class="fas fa-key"></i>Mes tokens<?php if (!empty($my_tokens)): ?><span class="cwp-ref-tab-count"><?= count($my_tokens); ?></span><?php endif; ?>
      </button>
    </div>

    <!-- â•â• Panel Parrain â•â• -->
    <div id="cwpRefPanelParrain" class="cwp-ref-panel is-active">

      <?php if (!empty($ref_rewards_sponsor)): ?>
      <div class="cwp-ref-section">
        <div class="cwp-ref-section-ttl cwp-ref-section-ttl--gold"><i class="fas fa-crown"></i>Vos r&eacute;compenses de Parrain</div>
        <div class="cwp-ref-cards-grid">
          <?php foreach ($ref_rewards_sponsor as $rw):
            $nom_lc = strtolower($rw->nom);
            if      (strpos($nom_lc, 'dp')      !== false) { $icon = 'fa-gem';   $rtype = 'purple'; }
            elseif  (strpos($nom_lc, 'remise')  !== false) { $icon = 'fa-tag';   $rtype = 'cyan';   }
            elseif  (strpos($nom_lc, 'monture') !== false) { $icon = 'fa-horse'; $rtype = 'gold';   }
            else                                            { $icon = 'fa-gift';  $rtype = 'gold';   }
          ?>
          <div class="cwp-ref-card cwp-ref-card--<?= $rtype; ?>">
            <div class="cwp-ref-card-ico"><i class="fas <?= $icon; ?>"></i></div>
            <div class="cwp-ref-card-body">
              <span class="cwp-ref-card-name"><?= htmlspecialchars($rw->nom); ?></span>
              <span class="cwp-ref-card-desc"><?= htmlspecialchars($rw->description ?? ''); ?><?php if ($rw->duree_en_jour > 0): ?><em class="cwp-ref-card-dur"><?= $rw->duree_en_jour; ?>&nbsp;jours</em><?php endif; ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="cwp-ref-section">
        <div class="cwp-ref-section-ttl"><i class="fas fa-tasks"></i>Comment &ccedil;a marche</div>
        <div class="cwp-ref-steps">
          <div class="cwp-ref-step">
            <div class="cwp-ref-step-num">1</div>
            <strong>G&eacute;n&eacute;rez un token</strong>
            <span>Envoyez-le &agrave; un ami qui ne joue pas encore sur Celestia</span>
          </div>
          <div class="cwp-ref-step-arrow"><i class="fas fa-chevron-right"></i></div>
          <div class="cwp-ref-step">
            <div class="cwp-ref-step-num">2</div>
            <strong>Il s&apos;inscrit avec le token</strong>
            <span>Le token est saisi lors de l&apos;inscription sur le site</span>
          </div>
          <div class="cwp-ref-step-arrow"><i class="fas fa-chevron-right"></i></div>
          <div class="cwp-ref-step cwp-ref-step--win">
            <div class="cwp-ref-step-num cwp-ref-step-num--win"><i class="fas fa-trophy"></i></div>
            <strong>R&eacute;compenses d&eacute;bloqu&eacute;es&nbsp;!</strong>
            <span>Apr&egrave;s <strong>3h de jeu</strong> et <strong>2&nbsp;persos&nbsp;&ge;&nbsp;niv.&nbsp;20</strong></span>
          </div>
        </div>
      </div>

      <div class="cwp-ref-footer">
        <div class="cwp-ref-limits">
          <span><i class="fas fa-ban"></i>Token valable <strong>une seule fois</strong> pour un <strong>nouveau compte</strong></span>
          <span><i class="fas fa-calendar-day"></i>Max <strong><?= $ref_cfg_row->max_tokens_per_day ?? 5; ?>&nbsp;tokens/jour</strong> &middot; Validit&eacute;&nbsp;: <strong><?= $ref_cfg_row->token_validity_days ?? 30; ?>&nbsp;jours</strong></span>
        </div>
        <div class="cwp-ref-gen-row">
          <div class="cwp-ref-gen-info">
            <?php if (!empty($user_email) && filter_var($user_email, FILTER_VALIDATE_EMAIL)): ?>
            <span><i class="fas fa-envelope"></i>&nbsp;Envoy&eacute; &agrave;&nbsp;<strong><?= htmlspecialchars($user_email); ?></strong></span>
            <span class="cwp-ref-gen-quota"><?= $tokens_today; ?>&nbsp;/&nbsp;<?= $ref_cfg_row->max_tokens_per_day ?? 5; ?>&nbsp;token(s) g&eacute;n&eacute;r&eacute;(s) aujourd&apos;hui</span>
            <?php else: ?>
            <span class="cwp-ref-email-err"><i class="fas fa-exclamation-triangle"></i>&nbsp;E-mail invalide &mdash; mettez &agrave; jour vos param&egrave;tres.</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($user_email) && filter_var($user_email, FILTER_VALIDATE_EMAIL) && $tokens_today < ($ref_cfg_row->max_tokens_per_day ?? 5)): ?>
          <button class="cwp-ref-gen-btn cwp-ref-gen-btn--gold" onclick="cwpGenerateToken()">
            <i class="fas fa-magic"></i>G&eacute;n&eacute;rer un token
          </button>
          <?php else: ?>
          <button class="cwp-ref-gen-btn" disabled style="opacity:.4;cursor:not-allowed">
            <i class="fas fa-ban"></i>Indisponible
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- â•â• Panel Filleul â•â• -->
    <div id="cwpRefPanelFilleul" class="cwp-ref-panel">

      <?php if (!empty($ref_rewards_referee)): ?>
      <div class="cwp-ref-section">
        <div class="cwp-ref-section-ttl cwp-ref-section-ttl--emerald"><i class="fas fa-star"></i>Vos r&eacute;compenses de Filleul</div>
        <div class="cwp-ref-cards-grid">
          <?php foreach ($ref_rewards_referee as $rw):
            $nom_lc = strtolower($rw->nom);
            if      (strpos($nom_lc, 'sesame') !== false || strpos($nom_lc, 's&eacute;same') !== false) { $icon = 'fa-bolt';   $rtype = 'emerald'; }
            elseif  (strpos($nom_lc, 'profession') !== false)                                           { $icon = 'fa-hammer'; $rtype = 'orange';  }
            elseif  (strpos($nom_lc, 'monture') !== false)                                              { $icon = 'fa-horse';  $rtype = 'gold';    }
            elseif  (strpos($nom_lc, 'remise') !== false)                                               { $icon = 'fa-tag';    $rtype = 'cyan';    }
            else                                                                                         { $icon = 'fa-gift';   $rtype = 'emerald'; }
          ?>
          <div class="cwp-ref-card cwp-ref-card--<?= $rtype; ?>">
            <div class="cwp-ref-card-ico"><i class="fas <?= $icon; ?>"></i></div>
            <div class="cwp-ref-card-body">
              <span class="cwp-ref-card-name"><?= htmlspecialchars($rw->nom); ?></span>
              <span class="cwp-ref-card-desc"><?= htmlspecialchars($rw->description ?? ''); ?><?php if ($rw->duree_en_jour > 0): ?><em class="cwp-ref-card-dur"><?= $rw->duree_en_jour; ?>&nbsp;jours</em><?php endif; ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="cwp-ref-section">
        <div class="cwp-ref-section-ttl cwp-ref-section-ttl--emerald"><i class="fas fa-tasks"></i>Comment en b&eacute;n&eacute;ficier</div>
        <div class="cwp-ref-steps cwp-ref-steps--emerald">
          <div class="cwp-ref-step">
            <div class="cwp-ref-step-num">1</div>
            <strong>Obtenez un token</strong>
            <span>Demandez un code de parrainage &agrave; un ami d&eacute;j&agrave; inscrit sur Celestia</span>
          </div>
          <div class="cwp-ref-step-arrow"><i class="fas fa-chevron-right"></i></div>
          <div class="cwp-ref-step">
            <div class="cwp-ref-step-num">2</div>
            <strong>Inscrivez-vous avec le token</strong>
            <span>Renseignez le token lors de votre inscription sur le site</span>
          </div>
          <div class="cwp-ref-step-arrow"><i class="fas fa-chevron-right"></i></div>
          <div class="cwp-ref-step cwp-ref-step--win cwp-ref-step--win-em">
            <div class="cwp-ref-step-num cwp-ref-step-num--win-em"><i class="fas fa-trophy"></i></div>
            <strong>R&eacute;compenses d&eacute;bloqu&eacute;es&nbsp;!</strong>
            <span>Jouez <strong>3h</strong> et cr&eacute;ez <strong>2&nbsp;persos&nbsp;&ge;&nbsp;niv.&nbsp;20</strong></span>
          </div>
        </div>
      </div>

      <div class="cwp-ref-footer">
        <div class="cwp-ref-limits">
          <span><i class="fas fa-wifi"></i>Validation par <strong>adresse IP</strong> &mdash; un seul parrainage par connexion</span>
          <span><i class="fas fa-ban"></i>Token utilisable <strong>une seule fois</strong>, pour les <strong>nouveaux comptes uniquement</strong></span>
        </div>
      </div>
    </div>

    <!-- â•â• Panel Tokens â•â• -->
    <div id="cwpRefPanelTokens" class="cwp-ref-panel">
      <div class="cwp-ref-tokens-hd"><i class="fas fa-key"></i>Mes tokens de parrainage</div>
      <div class="cwp-ref-token-list">
        <?php if (empty($my_tokens)): ?>
        <div class="cwp-ref-token-empty">Aucun token g&eacute;n&eacute;r&eacute; pour l&apos;instant.</div>
        <?php else: ?>
          <?php foreach ($my_tokens as $tk): ?>
          <div class="cwp-ref-token-row">
            <span class="cwp-ref-token-code" onclick="cwpCopyToken('<?= $tk->token; ?>')" title="Cliquer pour copier"><?= $tk->token; ?></span>
            <button class="cwp-ref-token-copy" onclick="cwpCopyToken('<?= $tk->token; ?>')" title="Copier"><i class="fas fa-copy"></i></button>
            <span class="cwp-ref-token-badge badge-<?= $tk->token_status === 'generated' ? 'pending' : $tk->token_status; ?>">
              <?php
              if      ($tk->token_status === 'generated') echo 'Actif';
              elseif  ($tk->token_status === 'used')      echo 'Utilis&eacute;';
              else                                         echo 'Expir&eacute;';
              ?>
            </span>
            <span style="font-size:1rem;color:var(--text-lo);white-space:nowrap">
              <?php if ($tk->token_status === 'generated'): ?>
                exp. <?= date('d/m/y', strtotime($tk->expires_at)); ?>
              <?php elseif ($tk->token_status === 'used'): ?>
                <?= $tk->referee_username ? htmlspecialchars($tk->referee_username) : 'utilis&eacute;'; ?>
              <?php else: ?>
                expir&eacute;
              <?php endif; ?>
            </span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="cwp-ref-generate" style="margin-top:1rem">
        <div class="cwp-ref-generate-info">
          <strong><?= max(0, ($ref_cfg_row->max_tokens_per_day ?? 5) - $tokens_today); ?></strong> g&eacute;n&eacute;ration(s) restante(s) aujourd&apos;hui
        </div>
        <?php if (!empty($user_email) && filter_var($user_email, FILTER_VALIDATE_EMAIL) && $tokens_today < ($ref_cfg_row->max_tokens_per_day ?? 5)): ?>
        <button class="cwp-ref-gen-btn" onclick="cwpGenerateToken()">
          <i class="fas fa-magic"></i>Nouveau token
        </button>
        <?php else: ?>
        <button class="cwp-ref-gen-btn" disabled style="opacity:.4;cursor:not-allowed"><i class="fas fa-ban"></i>Indisponible</button>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
<form id="cwpReferralForm" method="POST" action="<?= current_url(); ?>" style="display:none">
  <input type="hidden" name="referral_action" value="generate">
  <input type="hidden" name="referral_nonce"  value="<?= $referral_nonce_val; ?>">
</form>

<div class="cwp-copy-toast" id="cwpCopyToast"></div>

<script>
(function(){
  /* ── Ouvrir / fermer modales ── */
  window.cwpOpen = function(id){ document.getElementById(id).classList.add('is-open'); document.body.style.overflow='hidden'; };
  window.cwpClose = function(id){ document.getElementById(id).classList.remove('is-open'); document.body.style.overflow=''; };
  document.querySelectorAll('.cwp-overlay').forEach(function(ov){
    ov.addEventListener('click', function(e){ if(e.target===ov) cwpClose(ov.id); });
  });
  document.addEventListener('keydown', function(e){
    if(e.key==='Escape') document.querySelectorAll('.cwp-overlay.is-open').forEach(function(ov){ cwpClose(ov.id); });
  });

  /* ── Unstuck ── */
  window.cwpSubmitUnstuck = function(){
    var sel=document.getElementById('cwpUnstuckSelect');
    if(!sel||!sel.value){ sel.style.borderColor='rgba(255,77,77,.6)'; sel.focus(); return; }
    document.getElementById('cwpUnstuckInput').value=sel.value;
    document.getElementById('cwpUnstuckForm').submit();
  };

  /* ── Transfert ── */
  window.cwpSubmitTransfer = function(){
    var sel=document.getElementById('cwpTransferSelect');
    var acct=document.getElementById('cwpTransferAccount');
    var ok=true;
    if(!sel||!sel.value){ sel.style.borderColor='rgba(255,204,0,.6)'; sel.focus(); ok=false; }
    if(!acct||!acct.value.trim()){ acct.style.borderColor='rgba(255,204,0,.6)'; if(ok) acct.focus(); ok=false; }
    if(!ok) return;
    if(!confirm('Confirmer le transfert de "'+sel.value+'" vers "'+acct.value+'" pour <?= $TRANSFER_COST; ?> DP ?')) return;
    document.getElementById('cwpTransferCharInput').value=sel.value;
    document.getElementById('cwpTransferAccountInput').value=acct.value.trim();
    document.getElementById('cwpTransferForm').submit();
  };

  /* Reset bordures */
  ['cwpUnstuckSelect','cwpTransferSelect','cwpTransferAccount'].forEach(function(id){
    var el=document.getElementById(id);
    if(el) el.addEventListener('focus', function(){ this.style.borderColor=''; });
  });

  /* Scroll vers notif */
  var notif=document.querySelector('.cwp__notif');
  if(notif) setTimeout(function(){ notif.scrollIntoView({behavior:'smooth',block:'center'}); },300);

  /* ── Tabs parrainage ── */
  window.cwpRefTab=function(tab){
    document.querySelectorAll('.cwp-ref-tab').forEach(function(t){ t.classList.remove('is-active'); });
    document.querySelectorAll('.cwp-ref-panel').forEach(function(p){ p.classList.remove('is-active'); });
    document.querySelector('.cwp-ref-tab[data-tab="'+tab+'"]').classList.add('is-active');
    document.getElementById('cwpRefPanel'+tab).classList.add('is-active');
  };

  /* ── Copier token ── */
  window.cwpCopyToken=function(token){
    if(navigator.clipboard){
      navigator.clipboard.writeText(token).then(function(){
        var toast=document.getElementById('cwpCopyToast');
        toast.textContent='✓ Token "'+token+'" copié !';
        toast.classList.add('show');
        setTimeout(function(){ toast.classList.remove('show'); },2000);
      });
    }
  };

  /* ── Générer token ── */
  window.cwpGenerateToken=function(){
    if(!confirm('Générer un nouveau token de parrainage ? Il sera valable <?= $ref_cfg_row->token_validity_days ?? 30; ?> jours.')) return;
    document.getElementById('cwpReferralForm').submit();
  };

  /* ── Sidebar animation (Mise à jour pour pulser en Cyan) ── */
  var widget=document.querySelector('.cwp-referral');
  if(widget){
    var ray=document.createElement('div');
    ray.className='cwp-referral__ray';
    widget.appendChild(ray);
    setInterval(function(){
      ray.classList.add('active');
      setTimeout(function(){ ray.classList.remove('active'); },900);
    },5000);
    var glowStep=0;
    setInterval(function(){
      glowStep=(glowStep+1)%2;
      widget.style.boxShadow=glowStep
        ?'0 0 18px rgba(0, 225, 255, 0.18),0 0 1px rgba(0, 225, 255, 0.1)'
        :'0 0 6px rgba(0, 225, 255, 0.06)';
    },2800);
  }

})();
</script>
