<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ticket — Celestia-WoW
 * ══════════════════════════════════════════════════════════
 * Endpoint de notification des tickets en jeu.
 * Appelé par le script Lua via curl depuis le worldserver.
 *
 * Route : POST /ticket/notify
 * ══════════════════════════════════════════════════════════
 */
class Ticket extends MX_Controller
{
    /** Clé secrète — doit correspondre à SECRET_KEY dans ticket_notify.lua */
    const SECRET_KEY = '->udBJK,d9xj/=r=/p]k%QI_mHQhrE4M';

    /** URL du webhook Discord */
    const DISCORD_WEBHOOK = 'https://canary.discord.com/api/webhooks/1484899886733463643/JQCMpixx_sKeDWQvy7Np73KQpslGSue-ZOLdWpWlRCjxozSjv3ay9ga1Ww1tFtBLJfm5';

    /** Port du plugin ModMail (pluginPort dans celestia-wow-plugin.js) */
    const MODMAIL_PLUGIN_PORT = 8890;

    /** Adresse expéditrice des mails */
    const MAIL_FROM      = 'wowcelestia@gmail.com';
    const MAIL_FROM_NAME = 'Celestia-WoW Staff';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('ticket_notify_model');
        $this->load->library('email');
    }

    /* ════════════════════════════════════════════════════
       POST /ticket/notify
       Reçoit le JSON du script Lua et dispatche les notifs.
    ════════════════════════════════════════════════════ */
    public function notify()
    {
        // Seules les requêtes POST sont acceptées
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        // Lecture et décodage du JSON
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo 'Bad Request';
            return;
        }

        // Vérification de la clé secrète
        if (!isset($data['secret']) || $data['secret'] !== self::SECRET_KEY) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        // Extraction et nettoyage des données
        $ticket_id = (int)   ($data['ticket_id'] ?? 0);
        $player    = htmlspecialchars($data['player']    ?? 'Inconnu', ENT_QUOTES);
        $message   = htmlspecialchars($data['message']   ?? '',        ENT_QUOTES);
        $location  = htmlspecialchars($data['location']  ?? 'Inconnue',ENT_QUOTES);
        $timestamp = (int)   ($data['timestamp'] ?? time());
        $date      = date('d/m/Y à H:i:s', $timestamp);

        if (!$ticket_id) {
            http_response_code(400);
            echo 'Missing ticket_id';
            return;
        }

        // Récupère la liste des GMs à notifier
        $staff = $this->ticket_notify_model->getActiveStaff();

        if (empty($staff)) {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'notified' => 0]);
            return;
        }

        // Dispatch ModMail Bot — crée le canal Discord dédié
        $modmail_sent = $this->_sendModMail($ticket_id, $player, $message, $location, $timestamp);

        // Dispatch Discord (webhook — notification générale)

        $discord_sent = $this->_sendDiscord($ticket_id, $player, $message, $location, $date, $staff);

        // Dispatch Mail
        $mail_sent = $this->_sendMails($ticket_id, $player, $message, $location, $date, $staff);

        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'status'         => 'ok',
            'ticket_id'      => $ticket_id,
            'discord_sent'   => $discord_sent,
            'mails_sent'     => $mail_sent,
            'modmail_sent'   => $modmail_sent,
        ]);
    }


    /* ════════════════════════════════════════════════════
       MODMAIL BOT — Crée un canal Discord dédié par ticket
       Appelle le plugin celestia-wow-plugin.js via HTTP
    ════════════════════════════════════════════════════ */
    private function _sendModMail(int $id, string $player, string $message, string $location, int $timestamp): bool
    {
        $payload = json_encode([
            'secret'    => self::SECRET_KEY,
            'ticket_id' => $id,
            'player'    => $player,
            'message'   => $message,
            'location'  => $location,
            'timestamp' => $timestamp,
        ]);

        $ch = curl_init('http://127.0.0.1:' . self::MODMAIL_PLUGIN_PORT . '/wow-ticket/create');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
        ]);
        $response = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('info', 'Ticket #' . $id . ' ModMail status: ' . $http . ' — ' . $response);
        return ($http === 200);
    }

    /* ════════════════════════════════════════════════════
       DISCORD — Webhook
    ════════════════════════════════════════════════════ */
    private function _sendDiscord(int $id, string $player, string $message, string $location, string $date, array $staff): bool
    {
        // Construit les mentions des GMs qui ont notify_discord = 1
        $mentions = [];
        foreach ($staff as $gm) {
            if ($gm->notify_discord && !empty($gm->discord_id)) {
                $mentions[] = '<@' . $gm->discord_id . '>';
            }
        }
        $mention_str = !empty($mentions) ? implode(' ', $mentions) . "\n" : '';

        // Embed Discord
        $embed = [
            'title'       => '🎫 Nouveau ticket #' . $id,
            'color'       => 0xC8A95A, // or WoW
            'description' => $mention_str . 'Un joueur a ouvert un ticket en jeu.',
            'fields'      => [
                ['name' => '👤 Joueur',      'value' => $player,   'inline' => true],
                ['name' => '📍 Position',    'value' => $location, 'inline' => true],
                ['name' => '📝 Message',     'value' => mb_substr($message, 0, 1024) ?: '*(vide)*', 'inline' => false],
            ],
            'footer'      => ['text' => 'Celestia-WoW • ' . $date],
            'timestamp'   => date('c'),
        ];

        $payload = json_encode([
            'username'   => 'Celestia Tickets',
            'avatar_url' => 'https://celestia-wow.fr/assets/images/logo.png', // adapte si besoin
            'embeds'     => [$embed],
        ]);

        $ch = curl_init(self::DISCORD_WEBHOOK);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('info', 'Ticket #' . $id . ' Discord status: ' . $http);
        return ($http >= 200 && $http < 300);
    }

    /* ════════════════════════════════════════════════════
       MAIL — CodeIgniter Email library (SMTP Gmail)
    ════════════════════════════════════════════════════ */
    private function _sendMails(int $id, string $player, string $message, string $location, string $date, array $staff): int
    {
        $sent = 0;

        // Filtre les GMs qui veulent recevoir les mails
        $recipients = array_filter($staff, fn($gm) => $gm->notify_email && !empty($gm->email));
        if (empty($recipients)) return 0;

        // Chargement de la config email depuis email.php
        $this->config->load("email", TRUE);
        $email_config = $this->config->item("email") ?: [];
        if (empty($email_config)) {
            $email_config = [
                "protocol"    => $this->config->item("protocol"),
                "smtp_host"   => $this->config->item("smtp_host"),
                "smtp_port"   => $this->config->item("smtp_port"),
                "smtp_user"   => $this->config->item("smtp_user"),
                "smtp_pass"   => $this->config->item("smtp_pass"),
                "smtp_crypto" => $this->config->item("smtp_crypto"),
                "smtp_timeout"=> $this->config->item("smtp_timeout"),
                "mailtype"    => $this->config->item("mailtype"),
                "charset"     => $this->config->item("charset"),
                "wordwrap"    => $this->config->item("wordwrap"),
                "newline"     => "\r\n",
                "crlf"        => "\r\n",
            ];
        }
        $this->email->initialize($email_config);

        $subject = '[Celestia-WoW] Nouveau ticket #' . $id . ' — ' . $player;

        $body = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body      { background:#0a0f1e; color:#d0dde8; font-family:Arial,sans-serif; margin:0; padding:0; }
  .wrap     { max-width:600px; margin:30px auto; background:#0d1526; border:1px solid rgba(200,169,90,.3); border-radius:8px; overflow:hidden; }
  .header   { background:linear-gradient(135deg,#0d1526,#1a2a4a); padding:28px 32px; border-bottom:2px solid #c8a95a; }
  .header h1{ margin:0; font-size:1.3rem; color:#c8a95a; letter-spacing:.1em; }
  .header p { margin:6px 0 0; font-size:.85rem; color:#8aa0b8; }
  .body     { padding:28px 32px; }
  .field    { margin-bottom:18px; }
  .label    { font-size:.75rem; text-transform:uppercase; letter-spacing:.12em; color:#8aa0b8; margin-bottom:4px; }
  .value    { background:#070d1a; border:1px solid rgba(168,184,200,.12); border-radius:4px; padding:10px 14px; font-size:.95rem; color:#d0dde8; white-space:pre-wrap; word-break:break-word; }
  .footer   { padding:16px 32px; border-top:1px solid rgba(168,184,200,.08); font-size:.75rem; color:#4a5a6a; text-align:center; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🎫 Nouveau Ticket #' . $id . '</h1>
    <p>Reçu le ' . $date . '</p>
  </div>
  <div class="body">
    <div class="field">
      <div class="label">👤 Joueur</div>
      <div class="value">' . $player . '</div>
    </div>
    <div class="field">
      <div class="label">📍 Position</div>
      <div class="value">' . $location . '</div>
    </div>
    <div class="field">
      <div class="label">📝 Message</div>
      <div class="value">' . nl2br($message) . '</div>
    </div>
  </div>
  <div class="footer">Celestia-WoW — Notification automatique. Ne pas répondre à cet email.</div>
</div>
</body>
</html>';

        foreach ($recipients as $gm) {
            $this->email->clear();
            $this->email->from(self::MAIL_FROM, self::MAIL_FROM_NAME);
            $this->email->to($gm->email);
            $this->email->subject($subject);
            $this->email->message($body);

            if ($this->email->send()) {
                $sent++;
                log_message('info', 'Ticket #' . $id . ' mail envoyé à ' . $gm->email);
            } else {
                log_message('error', 'Ticket #' . $id . ' échec mail ' . $gm->email . ' — ' . $this->email->print_debugger(['headers']));
            }
        }

        return $sent;
    }

    /* ════════════════════════════════════════════════════
       POST /ticket/close
       Appelé par Lua quand un ticket est fermé en jeu.
       Notifie le plugin ModMail pour supprimer le canal.
    ════════════════════════════════════════════════════ */
    public function close()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405); echo 'Method Not Allowed'; return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data) || ($data['secret'] ?? '') !== self::SECRET_KEY) {
            http_response_code(403); echo 'Forbidden'; return;
        }

        $ticket_id = (int) ($data['ticket_id'] ?? 0);
        if (!$ticket_id) {
            http_response_code(400); echo 'Missing ticket_id'; return;
        }

        // Notifie le plugin ModMail pour supprimer le canal Discord
        $payload = json_encode(['secret' => self::SECRET_KEY, 'ticket_id' => $ticket_id]);
        $ch = curl_init('http://127.0.0.1:' . self::MODMAIL_PLUGIN_PORT . '/wow-ticket/close');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
        ]);
        $response = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('info', 'Ticket #' . $ticket_id . ' close ModMail status: ' . $http);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'ticket_id' => $ticket_id, 'modmail_status' => $http]);
    }

}
