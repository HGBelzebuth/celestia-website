<?php
/**
 * User.php — Méthode activate() et register() retravaillées
 *
 * Changements vs version originale :
 *  - register() : pré-réserve le token parrainage, stocke referee_email,
 *                 envoi email d'activation HTML amélioré
 *  - activate() : déblocage compte + email filleul avec codes en_jeu
 *                 (referral_rewards ids 4,5,6) — NE distribue PAS les DP
 *                 ni les coupons boutique (délégué au cron)
 */

defined('BASEPATH') or exit('No direct script access allowed');

class User extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        if (!ini_get('date.timezone')) {
            date_default_timezone_set($this->config->item('timezone'));
        }
    }

    /* ──────────────────────────────────────────────────────────────────────
       LOGIN
    ────────────────────────────────────────────────────────────────────── */
    public function login()
    {
        if (!$this->wowmodule->getLoginStatus())  { redirect(base_url(), 'refresh'); }
        if ($this->wowauth->isLogged())           { redirect(base_url(), 'refresh'); }

        $data = [
            'pagetitle' => $this->lang->line('tab_login'),
            'recapKey'  => $this->config->item('recaptcha_sitekey'),
            'lang'      => $this->lang->lang(),
        ];

        if ($this->input->method() === 'post') {
            $rules = [
                ['field' => 'username', 'label' => 'Username/Email', 'rules' => 'trim|required'],
                ['field' => 'password', 'label' => 'Password',       'rules' => 'trim|required'],
            ];
            $this->form_validation->set_rules($rules);

            if ($this->form_validation->run() === false) {
                $this->template->build('login', $data);
            } else {
                $response = $this->user_model->authentication(
                    $this->input->post('username', true),
                    $this->input->post('password')
                );
                if ($response === 'email_not_verified') {
                    $data['msg_email_not_verified'] = $this->input->post('username', true);
                    $this->template->build('login', $data);
                } elseif (!$response) {
                    $data['msg_error_login'] = lang('notification_user_error');
                    $this->template->build('login', $data);
                } else {
                    redirect(site_url());
                }
            }
        } else {
            $this->template->build('login', $data);
        }
    }

    /* ──────────────────────────────────────────────────────────────────────
       REGISTER
    ────────────────────────────────────────────────────────────────────── */
    public function register()
    {
        if (!$this->wowgeneral->getMaintenance())  { redirect(base_url('maintenance'), 'refresh'); }
        if (!$this->wowmodule->getRegisterStatus()) { redirect(base_url(), 'refresh'); }
        if ($this->wowauth->isLogged())             { redirect(base_url(), 'refresh'); }

        $data = [
            'pagetitle' => $this->lang->line('tab_register'),
            'recapKey'  => $this->config->item('recaptcha_sitekey'),
            'lang'      => $this->lang->lang(),
        ];

        if ($this->input->method() === 'post') {
            $rules = [
                ['field' => 'username',         'label' => 'Username',         'rules' => 'trim|required|alpha_numeric|min_length[3]|max_length[16]|differs[nickname]'],
                ['field' => 'email',            'label' => 'Email',            'rules' => 'trim|required|valid_email'],
                ['field' => 'password',         'label' => 'Password',         'rules' => 'trim|required|alpha_numeric|min_length[8]'],
                ['field' => 'confirm_password', 'label' => 'Confirm password', 'rules' => 'trim|required|min_length[8]|matches[password]'],
            ];
            $this->form_validation->set_rules($rules);

            if ($this->form_validation->run() === false) {
                $this->template->build('register', $data);
                return;
            }

            $username = $this->input->post('username', true);
            $email    = $this->input->post('email', true);
            $password = $this->input->post('password');
            $emulator = $this->config->item('emulator');

            /* ── Nettoyage des inscriptions expirées non confirmées ─────────────
               Permet la réinscription immédiate avec le même email/username
               si le précédent compte n'a jamais été confirmé et a expiré.
            ─────────────────────────────────────────────────────────────── */
            $realm_c  = $this->wowrealm->getRealms()->row();
            $authDB_c = $this->wowrealm->realmConnection(
                $realm_c->username, $realm_c->password, $realm_c->hostname,
                $realm_c->auth_database ?? 'R0_Auth'
            );
            $webDB_c  = $this->wowrealm->realmConnection(
                $realm_c->username, $realm_c->password, $realm_c->hostname,
                $realm_c->web_database  ?? 'R0_Website'
            );
            $this->_purge_expired_registrations($webDB_c, $authDB_c);

            /* ── Validation code parrainage (avant création du compte) ─────── */
            $ref_check = strtoupper(trim($this->input->post('referral_token') ?? ''));
            if (!empty($ref_check)) {
                $rt_any = $webDB_c->where('token', $ref_check)->get('referral_tokens')->row();
                if (!$rt_any) {
                    $data['msg_referral_invalid'] = 'Code de parrainage invalide ou inexistant.';
                    $this->template->build('register', $data);
                    return;
                }
                if ($rt_any->token_status !== 'generated') {
                    $data['msg_referral_invalid'] = 'Ce code de parrainage a déjà été utilisé.';
                    $this->template->build('register', $data);
                    return;
                }
                if (strtotime($rt_any->expires_at) < time()) {
                    $data['msg_referral_invalid'] = 'Ce code de parrainage est périmé.';
                    $this->template->build('register', $data);
                    return;
                }
            }
            /* ───────────────────────────────────────────────────────────────── */

            if (!$this->wowauth->account_unique($username, 'username')) {
                $data['msg_notification_account_already_exist'] = lang('notification_account_already_exist');
                $this->template->build('register', $data);
                return;
            }
            if (!$this->wowauth->account_unique($email, 'email')) {
                $data['msg_notification_used_email'] = lang('notification_used_email');
                $this->template->build('register', $data);
                return;
            }

            $register = $this->user_model->insertRegister($username, $email, $password, $emulator);

            if (!$register) {
                $data['msg_notification_account_not_created'] = lang('notification_account_not_created');
                $this->template->build('register', $data);
                return;
            }

            /* ── Connexions DB ── */
            $realm  = $this->wowrealm->getRealms()->row();
            $authDB = $this->wowrealm->realmConnection(
                $realm->username, $realm->password, $realm->hostname,
                $realm->auth_database ?? 'R0_Auth'
            );
            $webDB  = $this->wowrealm->realmConnection(
                $realm->username, $realm->password, $realm->hostname,
                $realm->web_database  ?? 'R0_Website'
            );

            /* Bloquer le compte en attente de confirmation email */
            $authDB->where('username', strtoupper($username))->update('account', ['locked' => 1]);

            /* Créer la ligne users si absente */
            if ($webDB->where('username', $username)->count_all_results('users') === 0) {
                $webDB->insert('users', ['username' => $username, 'email' => $email, 'dp' => 0, 'vp' => 0]);
            }

            /* Table email_confirmations */
            $webDB->query("CREATE TABLE IF NOT EXISTS `email_confirmations` (
                `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username`     VARCHAR(50)  NOT NULL,
                `email`        VARCHAR(180) NOT NULL,
                `token`        CHAR(48)     NOT NULL UNIQUE,
                `expires_at`   DATETIME     NOT NULL,
                `confirmed_at` DATETIME     NULL DEFAULT NULL,
                `status`       ENUM('pending','confirmed','expired') NOT NULL DEFAULT 'pending',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $conf_token = bin2hex(random_bytes(24));
            $webDB->insert('email_confirmations', [
                'username'   => $username,
                'email'      => $email,
                'token'      => $conf_token,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            ]);

            /* ────────────────────────────────────────────────────────────
               PARRAINAGE — pré-réservation du token
            ─────────────────────────────────────────────────────────── */
            $ref_token_input = strtoupper(trim($this->input->post('referral_token') ?? ''));
            if (!empty($ref_token_input)) {
                $rt = $webDB->where('token', $ref_token_input)
                             ->where('token_status', 'generated')
                             ->where('expires_at >=', date('Y-m-d H:i:s'))
                             ->get('referral_tokens')->row();

                if ($rt && strtolower($rt->sponsor_username) !== strtolower($username)) {
                    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

                    /* Anti-abus : un compte = un seul parrainage + IP différente du parrain */
                    $ref_already = $webDB->where('referee_username', $username)
                                          ->count_all_results('referral_tokens');

                    if ($ref_already === 0 && (empty($user_ip) || $user_ip !== $rt->sponsor_ip)) {
                        /* Pré-réserver : renseigne referee mais laisse token_status = 'generated' */
                        $webDB->where('token', $ref_token_input)->update('referral_tokens', [
                            'referee_username' => $username,
                            'referee_email'    => $email,
                            'referee_ip'       => $user_ip,
                        ]);
                    }
                }
            }

            /* ── Email de confirmation ── */
            $conf_url   = base_url($this->lang->lang() . '/activate/' . $conf_token);
            $site_name  = 'Celestia-WoW';
            $site_url   = base_url();

            $mail_body = $this->_build_confirm_email($username, $email, $conf_url, $site_name, $site_url);
            $this->load->library('email');
            $this->email->initialize(['mailtype' => 'html', 'charset' => 'utf-8']);
            $this->email->clear();
            $this->email->from('wowcelestia@gmail.com', $site_name);
            $this->email->to($email);
            $this->email->subject('[' . $site_name . '] Activez votre compte');
            $this->email->message($mail_body);
            $this->email->send();
            unset($mail_body);

            $this->session->set_flashdata('reg_success_email', $email);
            redirect(site_url('register'));

        } else {
            $this->template->build('register', $data);
        }
    }

    /* ──────────────────────────────────────────────────────────────────────
       ACTIVATE
       Workflow parrainage :
         1. Débloquer le compte
         2. Si token parrainage pré-réservé :
              - marquer token_status = 'used'
              - laisser referral_status = 'pending' (le cron fera le reste)
              - envoyer email filleul avec codes en_jeu ids 4,5,6
         3. Auto-login
    ────────────────────────────────────────────────────────────────────── */
    public function activate($token = '')
    {
        if (empty($token)) { redirect(base_url()); return; }

        $token  = preg_replace('/[^A-Za-z0-9]/', '', $token);
        $realm  = $this->wowrealm->getRealms()->row();
        $webDB  = $this->wowrealm->realmConnection(
            $realm->username, $realm->password, $realm->hostname,
            $realm->web_database  ?? 'R0_Website'
        );
        $authDB = $this->wowrealm->realmConnection(
            $realm->username, $realm->password, $realm->hostname,
            $realm->auth_database ?? 'R0_Auth'
        );

        $row = $webDB->where('token', $token)->get('email_confirmations')->row();

        if (!$row) {
            redirect(site_url('register')); return;
        }
        if (strtotime($row->expires_at) < time()) {
            $this->session->set_flashdata('confirm_expired_email', $row->email);
            redirect(site_url('resend_confirm')); return;
        }

        if ($row->status === 'pending') {
            $now = date('Y-m-d H:i:s');

            /* 1. Débloquer le compte */
            $authDB->where('username', strtoupper($row->username))->update('account', ['locked' => 0]);
            $webDB->where('token', $token)->update('email_confirmations', [
                'status'       => 'confirmed',
                'confirmed_at' => $now,
            ]);

            /* 2. Parrainage */
            $pending_ref = $webDB->where('referee_username', $row->username)
                                  ->where('token_status', 'generated')
                                  ->get('referral_tokens')->row();

            if ($pending_ref) {
                /* Marquer le token comme utilisé — referral_status reste 'pending' pour le cron */
                $webDB->where('token', $pending_ref->token)->update('referral_tokens', [
                    'token_status' => 'used',
                    'used_at'      => $now,
                    'referee_email'=> $row->email,
                    'referee_ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);

                /* Email filleul : génère codes uniques + envoi */
                $elunaDB = $this->wowrealm->realmConnection(
                    $realm->username, $realm->password, $realm->hostname, 'R1_Eluna'
                );
                $this->_send_referee_activation_email($webDB, $elunaDB, $row->username, $row->email, $pending_ref->token);
            }
        }

        /* 3. Auto-login */
        $acc = $authDB->where('username', strtoupper($row->username))->get('account')->row();
        if ($acc) {
            $this->session->set_userdata('wow_sess_id',         $acc->id);
            $this->session->set_userdata('blizz_sess_username', $acc->username);
            $this->session->set_userdata('wow_sess_username',   $acc->username);
        }

        $this->session->set_flashdata('account_just_activated', $row->username);
        redirect(base_url());
    }

    /* ──────────────────────────────────────────────────────────────────────
       Envoie l'email filleul à l'activation de son compte.
       Contient les codes en_jeu ids 4, 5, 6 de referral_rewards.
    ────────────────────────────────────────────────────────────────────── */
    private function _send_referee_activation_email($webDB, $elunaDB, string $username, string $email, string $token_ref): void
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

        $site_name = 'Celestia-WoW';
        $site_url  = base_url();

        /* Récompenses filleul configurées (ids 4,5,6) */
        $rwd_rows = $webDB->where_in('id', [4, 5, 6])
                          ->where('active', 1)
                          ->order_by('id', 'ASC')
                          ->get('referral_rewards')->result();

        if (empty($rwd_rows)) return;

        /* Idempotence : codes déjà générés pour ce filleul ? */
        $already = $webDB->where('referee_username', $username)
                         ->where('token_ref', $token_ref)
                         ->get('referral_ingame_codes')->result();

        $generated_codes = [];

        if (!empty($already)) {
            foreach ($already as $row) {
                $generated_codes[] = ['nom' => $row->reward_nom, 'code' => $row->unique_code];
            }
        } else {
            $chars_pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

            foreach ($rwd_rows as $rw) {
                if (empty($rw->code_en_jeu)) continue;

                /* Code parent — enabled ignoré (parents volontairement désactivés pour joueurs) */
                $pchk = $elunaDB->where('code', $rw->code_en_jeu)->get('player_code')->row();
                if (!$pchk) continue;

                /* Récompense du parent */
                $prwd = $elunaDB->where('code', $rw->code_en_jeu)->get('player_code_rewards')->row();
                if (!$prwd) continue;

                /* Code unique RF + 8 chars */
                $unique = ''; $attempts = 0;
                do {
                    $unique = 'RF';
                    for ($i = 0; $i < 8; $i++) {
                        $unique .= $chars_pool[random_int(0, strlen($chars_pool) - 1)];
                    }
                    $dup = $webDB->where('unique_code', $unique)->get('referral_ingame_codes')->row();
                    $attempts++;
                } while ($dup && $attempts < 20);

                /* Insérer dans R1_Eluna.player_code (usage unique joueur) */
                $elunaDB->insert('player_code', [
                    'code'           => $unique,
                    'use_count'      => 1,
                    'start_time'     => date('Y-m-d H:i:s'),
                    'end_time'       => date('Y-m-d H:i:s', strtotime('+5 years')),
                    'enabled'        => 1,
                    'for_new_player' => 0,
                ]);

                /* Copier la récompense du parent */
                $elunaDB->insert('player_code_rewards', [
                    'code'       => $unique,
                    'item_entry' => $prwd->item_entry,
                    'quantity'   => $prwd->quantity,
                    'money'      => $prwd->money,
                ]);

                /* Stocker dans R0_Website pour le panel */
                $webDB->insert('referral_ingame_codes', [
                    'token_ref'        => $token_ref,
                    'referee_username' => $username,
                    'reward_id'        => $rw->id,
                    'reward_nom'       => $rw->nom,
                    'unique_code'      => $unique,
                    'parent_code'      => $rw->code_en_jeu,
                ]);

                $generated_codes[] = ['nom' => $rw->nom, 'code' => $unique];
            }
        }

        if (empty($generated_codes)) return;

        $u       = htmlspecialchars($username,  ENT_QUOTES, 'UTF-8');
        $sn      = htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8');
        $su      = htmlspecialchars($site_url,  ENT_QUOTES, 'UTF-8');

        /* Récupère le nom du parrain depuis referral_tokens */
        $parrain_row = $webDB->where('token', $token_ref)->select('sponsor_username')->get('referral_tokens')->row();
        $parrain_name = $parrain_row ? htmlspecialchars($parrain_row->sponsor_username, ENT_QUOTES, 'UTF-8') : '';

        $codes_rows = '';
        foreach ($generated_codes as $gc) {
            $codes_rows .=
                '<tr>'
                . '<td style="padding:10px 16px;font-family:Courier New,monospace;font-size:1.15rem;letter-spacing:.2em;font-weight:700;color:#7dd89a;border-bottom:1px solid rgba(93,190,122,.15)">'
                . htmlspecialchars($gc['code'], ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '<td style="padding:10px 16px;font-size:.9rem;color:#8da5bb;border-bottom:1px solid rgba(93,190,122,.15);vertical-align:middle">'
                . htmlspecialchars($gc['nom'],  ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '</tr>';
        }

        $parrain_block = $parrain_name
            ? '<p style="margin:0 0 18px;font-size:.95rem">Vous avez &eacute;t&eacute; parraint&eacute; par <strong style="color:#00e1ff">' . $parrain_name . '</strong>. Bienvenue dans l\'aventure&nbsp;!</p>'
            : '';

        $mail_html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Parrainage valid&eacute; &mdash; {$sn}</title><style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#06111c;font-family:Georgia,serif;color:#8da5bb}
a{color:#6a8aaa;text-decoration:none}
</style></head>
<body style="margin:0;padding:32px 16px;background:#06111c">
<div style="max-width:580px;margin:0 auto;background:#0a1a2c;border:1px solid rgba(180,200,220,.16);border-radius:12px;overflow:hidden">

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#0d1f36 0%,#091525 100%);padding:32px;border-bottom:1px solid rgba(180,200,220,.12)">
    <div style="font-size:.72rem;letter-spacing:.18em;text-transform:uppercase;color:#3a6a9a;margin-bottom:8px">Celestia&nbsp;WoW &mdash; Parrainage</div>
    <h1 style="font-size:1.4rem;color:#c8d4e0;font-weight:normal;line-height:1.3">
      &#127881;&nbsp;F&eacute;licitations,&nbsp;{$u}&nbsp;!<br>
      <span style="font-size:1rem;color:#6a9aaa">Votre parrainage a &eacute;t&eacute; valid&eacute;.</span>
    </h1>
  </div>

  <!-- Body -->
  <div style="padding:28px 32px;line-height:1.75">

    {$parrain_block}

    <!-- Codes -->
    <p style="font-size:.8rem;letter-spacing:.12em;text-transform:uppercase;color:#3a6a8a;margin-bottom:10px">Vos r&eacute;compenses &mdash; codes &agrave; usage unique</p>
    <div style="background:rgba(93,190,122,.06);border:1px solid rgba(93,190,122,.22);border-radius:8px;overflow:hidden;margin-bottom:20px">
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
        <thead>
          <tr style="background:rgba(93,190,122,.08)">
            <th style="padding:8px 16px;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#3a7a4a;text-align:left;font-weight:normal">Code</th>
            <th style="padding:8px 16px;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#3a7a4a;text-align:left;font-weight:normal">R&eacute;compense</th>
          </tr>
        </thead>
        <tbody>
          {$codes_rows}
        </tbody>
      </table>
    </div>

    <!-- Instructions -->
    <p style="font-size:.8rem;letter-spacing:.12em;text-transform:uppercase;color:#3a6a8a;margin-bottom:10px">Comment utiliser vos codes en jeu</p>
    <ol style="padding-left:20px;margin-bottom:20px;font-size:.93rem;line-height:2">
      <li>Connectez-vous au serveur <strong style="color:#c8d4e0">Celestia&nbsp;WoW</strong> avec n&apos;importe quel personnage.</li>
      <li>Cherchez et approchez le <strong style="color:#c8d4e0">PNJ Tyra&euml;l</strong> (disponible en zone de d&eacute;part).</li>
      <li>Faites un <strong style="color:#c8d4e0">clic droit</strong> sur lui &rarr; choisissez <em>&laquo;&thinsp;Je souhaite entrer un code promo.&thinsp;&raquo;</em></li>
      <li>Saisissez votre code <strong>exactement</strong> tel qu&apos;indiqu&eacute; ci-dessus (majuscules) et validez avec&nbsp;<em>Accepter</em>.</li>
      <li>Vos r&eacute;compenses sont cr&eacute;dit&eacute;es <strong style="color:#c8d4e0">instantan&eacute;ment</strong>&nbsp;!</li>
    </ol>

    <!-- Note -->
    <div style="padding:12px 16px;background:rgba(200,168,75,.07);border:1px solid rgba(200,168,75,.2);border-radius:6px;font-size:.88rem;color:#c8a84b;margin-bottom:20px">
      &#9888;&nbsp;Ces codes sont <strong>strictement personnels</strong> et ne peuvent &ecirc;tre utilis&eacute;s <strong>qu&apos;une seule fois</strong>. Ne les partagez pas.
    </div>

    <!-- Progression -->
    <div style="padding:14px 16px;background:rgba(180,200,220,.04);border:1px solid rgba(180,200,220,.1);border-radius:6px;font-size:.88rem;color:#8da5bb">
      <strong style="color:#c8d4e0">&#127873;&nbsp;Prochaine &eacute;tape &mdash; Coupon boutique&nbsp;:</strong><br>
      Atteignez <strong style="color:#c8d4e0">3&nbsp;heures de jeu</strong> et poss&eacute;dez
      <strong style="color:#c8d4e0">2&nbsp;personnages de niveau&nbsp;&ge;&nbsp;20</strong>
      pour d&eacute;bloquer votre coupon boutique exclusif filleul.
      Vous recevrez un email automatiquement d&egrave;s que les conditions sont remplies.
    </div>

  </div>

  <!-- Footer -->
  <div style="padding:16px 32px;background:rgba(0,0,0,.3);font-size:.8rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.05)">
    <a href="{$su}">{$sn}</a> &mdash; Email automatique, merci de ne pas r&eacute;pondre.
  </div>

</div>
</body></html>
HTML;

        $this->load->library('email');
        $this->email->initialize(['mailtype' => 'html', 'charset' => 'utf-8']);
        $this->email->clear();
        $this->email->from('wowcelestia@gmail.com', $site_name);
        $this->email->to($email);
        $this->email->subject('[' . $site_name . '] ð FÃ©licitations ! Votre parrainage est validÃ©');
        $this->email->message($mail_html);
        $this->email->send();
        unset($mail_html);
    }

    /* ──────────────────────────────────────────────────────────────────────
       Email de confirmation de compte (HTML)
    ────────────────────────────────────────────────────────────────────── */
    private function _build_confirm_email(string $username, string $email, string $conf_url,
                                          string $site_name, string $site_url): string
    {
        $u   = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($conf_url, ENT_QUOTES, 'UTF-8');
        $sn  = htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8');
        $su  = htmlspecialchars($site_url, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{margin:0;padding:0;background:#06111c;font-family:Georgia,serif;color:#8da5bb;}
.wrap{max-width:560px;margin:40px auto;background:#0a1a2c;border:1px solid rgba(180,200,220,.18);border-radius:10px;overflow:hidden;}
.hd{background:linear-gradient(135deg,#0d1f36,#0a1628);padding:28px 32px;border-bottom:1px solid rgba(180,200,220,.14);}
.hd h1{margin:0;font-size:1.3rem;color:#c8d4e0;letter-spacing:.08em;}
.bd{padding:28px 32px;line-height:1.7;}
.btn{display:inline-block;margin:18px 0;padding:12px 28px;background:linear-gradient(135deg,#8a9fb5,#c8d4e0);border-radius:8px;font-family:Georgia,serif;font-size:1rem;letter-spacing:.08em;color:#020910;text-decoration:none;font-weight:bold;}
.note{margin-top:14px;padding:10px 14px;background:rgba(180,200,220,.05);border:1px solid rgba(180,200,220,.1);border-radius:6px;font-size:.9rem;color:#4a6278;}
.ft{padding:14px 32px;background:rgba(0,0,0,.3);font-size:.85rem;color:#4a6278;text-align:center;border-top:1px solid rgba(255,255,255,.05);}
.ft a{color:#6a8aaa;text-decoration:none;}
</style></head>
<body><div class="wrap">
<div class="hd"><h1>&#9993; Activez votre compte {$sn}</h1></div>
<div class="bd">
<p>Bonjour <strong style="color:#c8d4e0">{$u}</strong>,</p>
<p style="margin-top:12px">Cliquez sur le bouton ci-dessous pour activer votre compte (valable <strong>30 minutes</strong>)&nbsp;:</p>
<p style="text-align:center"><a class="btn" href="{$url}">Activer mon compte</a></p>
<div class="note">
  Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur&nbsp;:<br>
  <span style="color:#6a8aaa;word-break:break-all">{$url}</span>
</div>
<p style="margin-top:16px;font-size:.9rem;color:#4a6278;">Si vous n'avez pas cr&eacute;&eacute; de compte, ignorez cet email.</p>
</div>
<div class="ft"><a href="{$su}">{$sn}</a> &mdash; Email automatique.</div>
</div></body></html>
HTML;
    }

    /* ──────────────────────────────────────────────────────────────────────
       Méthodes originales conservées (panel, settings, logout, recovery, etc.)
    ────────────────────────────────────────────────────────────────────── */
    public function logout()
    {
        $this->wowauth->logout();
    }

    public function recovery()
    {
        if (!$this->wowgeneral->getMaintenance())  { redirect(base_url('maintenance'), 'refresh'); }
        if (!$this->wowmodule->getRecoveryStatus()) { redirect(base_url(), 'refresh'); }
        if ($this->wowauth->isLogged())             { redirect(base_url(), 'refresh'); }

        $data = [
            'pagetitle' => $this->lang->line('tab_reset'),
            'recapKey'  => $this->config->item('recaptcha_sitekey'),
            'lang'      => $this->lang->lang(),
        ];
        $this->template->build('recovery', $data);
    }

    public function forgotpassword()
    {
        echo $this->user_model->sendpassword(
            $this->input->post('username'),
            $this->input->post('email')
        );
    }

    public function panel()
    {
        if (!$this->wowgeneral->getMaintenance()) { redirect(base_url(), 'refresh'); }
        if (!$this->wowmodule->getUCPStatus())    { redirect(base_url(), 'refresh'); }
        if (!$this->wowauth->isLogged())          { redirect(base_url(), 'refresh'); }

        $data = ['pagetitle' => $this->lang->line('tab_account'), 'lang' => $this->lang->lang()];
        $this->template->build('panel', $data);
    }

    public function settings()
    {
        if (!$this->wowgeneral->getMaintenance()) { redirect(base_url(), 'refresh'); }
        if (!$this->wowmodule->getUCPStatus())    { redirect(base_url(), 'refresh'); }
        if (!$this->wowauth->isLogged())          { redirect(base_url(), 'refresh'); }

        $data = ['pagetitle' => $this->lang->line('tab_account'), 'lang' => $this->lang->lang()];
        $this->template->build('settings', $data);
    }

    public function newusername()
    {
        if (!$this->wowgeneral->getMaintenance()) { redirect(base_url('maintenance'), 'refresh'); }

        if ($this->input->method() === 'post') {
            $rules = [
                ['field' => 'newusername',    'label' => 'New username',    'rules' => 'trim|required'],
                ['field' => 'confirmusername','label' => 'Confirm Username','rules' => 'trim|required|matches[newusername]'],
            ];
            $this->form_validation->set_rules($rules);

            if ($this->form_validation->run() === false) {
                redirect(base_url('settings'), 'refresh');
            } else {
                $username    = $this->wowauth->getSiteUsernameID($this->session->userdata('wow_sess_id'));
                $newusername = $this->input->post('newusername', true);
                $password    = $this->input->post('password');
                $change      = $this->user_model->changeUsername($username, $newusername, $password);
                redirect($change ? site_url('logout') : site_url('settings'), 'refresh');
            }
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function newpass()
    {
        if (!$this->wowgeneral->getMaintenance()) { redirect(base_url('maintenance'), 'refresh'); }

        if ($this->input->method() === 'post') {
            $rules = [
                ['field' => 'change_oldpass',             'label' => 'Old password',    'rules' => 'trim|required'],
                ['field' => 'change_password',            'label' => 'New password',    'rules' => 'trim|required'],
                ['field' => 'change_renewchange_password','label' => 'Confirm password','rules' => 'trim|required|matches[change_password]'],
            ];
            $this->form_validation->set_rules($rules);

            if ($this->form_validation->run() === false) {
                redirect(base_url('settings'), 'refresh');
            } else {
                $oldpass = $this->input->post('change_oldpass');
                $newpass = $this->input->post('change_password');
                if (!$this->wowauth->valid_password($this->session->userdata('wow_sess_username'), $oldpass)) {
                    redirect(site_url('settings')); return;
                }
                $change = $this->user_model->changePassword($newpass);
                redirect($change ? site_url('logout') : site_url('settings'), 'refresh');
            }
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function newemail()
    {
        if (!$this->wowgeneral->getMaintenance()) { redirect(base_url('maintenance'), 'refresh'); }

        if ($this->input->method() === 'post') {
            $rules = [
                ['field' => 'change_newemail',  'label' => 'New email',    'rules' => 'trim|required'],
                ['field' => 'change_renewemail','label' => 'Confirm email','rules' => 'trim|required|matches[change_newemail]'],
                ['field' => 'change_password',  'label' => 'Password',     'rules' => 'trim|required'],
            ];
            $this->form_validation->set_rules($rules);

            if ($this->form_validation->run() === false) {
                redirect(base_url('settings'), 'refresh');
            } else {
                $email    = $this->wowauth->getEmailID($this->session->userdata('wow_sess_id'));
                $newemail = $this->input->post('change_newemail', true);
                $password = $this->input->post('change_password');
                $change   = $this->user_model->changeEmail($email, $newemail, $password);
                redirect($change ? site_url('logout') : site_url('settings'), 'refresh');
            }
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    public function newavatar()
    {
        $avatar = $this->input->post('change_avatar');
        $change = $this->user_model->changeAvatar($avatar);
        redirect($change ? site_url('panel') : site_url('settings'), 'refresh');
    }

    /* ──────────────────────────────────────────────────────────────────────
       RESEND CONFIRMATION EMAIL
    ────────────────────────────────────────────────────────────────────── */
    public function resend_confirm()
    {
        if ($this->wowauth->isLogged()) { redirect(base_url()); return; }

        $lang = $this->lang->lang();
        $data = ['pagetitle' => "Renvoyer l'email de confirmation", 'lang' => $lang, 'msg' => null];

        if ($this->input->method() === 'post') {
            $email = trim($this->input->post('email', true));

            $realm  = $this->wowrealm->getRealms()->row();
            $webDB  = $this->wowrealm->realmConnection(
                $realm->username, $realm->password, $realm->hostname,
                $realm->web_database ?? 'R0_Website'
            );
            $authDB = $this->wowrealm->realmConnection(
                $realm->username, $realm->password, $realm->hostname,
                $realm->auth_database ?? 'R0_Auth'
            );

            $row = $webDB->where('email', $email)->where_in('status', ['pending', 'expired'])
                         ->order_by('id', 'DESC')->limit(1)->get('email_confirmations')->row();

            if ($row) {
                $acc = $authDB->where('username', strtoupper($row->username))->where('locked', 1)->get('account')->row();
                if ($acc) {
                    $new_token = bin2hex(random_bytes(24));
                    $webDB->where('id', $row->id)->update('email_confirmations', [
                        'token'      => $new_token,
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                        'status'     => 'pending',
                    ]);

                    $conf_url  = base_url($lang . '/activate/' . $new_token);
                    $site_name = 'Celestia-WoW';
                    $mail_body = $this->_build_confirm_email($row->username, $email, $conf_url, $site_name, base_url());
                    $this->load->library('email');
                    $this->email->initialize(['mailtype' => 'html', 'charset' => 'utf-8']);
                    $this->email->clear();
                    $this->email->from('wowcelestia@gmail.com', $site_name);
                    $this->email->to($email);
                    $this->email->subject('[' . $site_name . '] Activez votre compte');
                    $this->email->message($mail_body);
                    $this->email->send();
                }
            }
            $data['msg'] = 'success';
            $this->template->build('resend_confirm', $data);
            return;
        }

        $data['prefill_email'] = $this->session->flashdata('confirm_expired_email') ?? '';
        $this->template->build('resend_confirm', $data);
    }

    /* ──────────────────────────────────────────────────────────────────────
       Supprime les inscriptions en attente de confirmation dont le lien
       a expiré. Appelée à chaque tentative d'inscription.
       Sécurité : ne supprime que les comptes locked=1 (jamais activés).
    ────────────────────────────────────────────────────────────────────── */
    private function _purge_expired_registrations($webDB, $authDB): void
    {
        $expired = $webDB
            ->select('username, email')
            ->where('status', 'pending')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->get('email_confirmations')
            ->result();

        foreach ($expired as $row) {
            /* Compte auth — uniquement si toujours verrouillé (non activé) */
            $authDB->where('username', strtoupper($row->username))
                   ->where('locked', 1)
                   ->delete('account');

            /* Ligne users */
            $webDB->where('username', $row->username)->delete('users');

            /* Pré-réservation de parrainage non encore utilisée */
            $webDB->where('referee_username', $row->username)
                  ->where('token_status', 'generated')
                  ->delete('referral_tokens');

            /* Log IP parrainage lié à cette inscription avortée */
            $webDB->where('username', $row->username)->delete('referral_ip_log');

            /* Confirmation email expirée */
            $webDB->where('username', $row->username)
                  ->where('status', 'pending')
                  ->delete('email_confirmations');
        }
    }

}
