<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * BlizzCMS — Changelogs
 * Fusionné avec les endpoints de synchronisation Discord → Site.
 */

class Changelogs extends MX_Controller {

    const SECRET_KEY = '->udBJK,d9xj/=r=/p]k%QI_mHQhrE4M';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('changelogs_model');

        if (!ini_get('date.timezone'))
            date_default_timezone_set($this->config->item('timezone'));

        // Les endpoints internes Discord ne passent pas par l'auth
        $method = $this->router->fetch_method();
        if (in_array($method, ['create', 'delete'])) return;

        if (!$this->wowgeneral->getMaintenance())
            redirect(base_url('maintenance'), 'refresh');

        if (!$this->wowmodule->getChangelogsStatus())
            redirect(base_url(), 'refresh');

        if (!$this->wowauth->isLogged())
            redirect(base_url('login'), 'refresh');
    }

    /* ════════════════════════════════════════════════════
       PAGE PUBLIQUE
    ════════════════════════════════════════════════════ */
    public function index()
    {
        $data = array(
            'pagetitle' => $this->lang->line('tab_changelogs'),
        );
        $this->template->build('index', $data);
    }

    /* ════════════════════════════════════════════════════
       POST /changelogs/create
       Appelé par le plugin ModMail quand un message est posté
       dans le canal #changelogs Discord.
    ════════════════════════════════════════════════════ */
    public function create()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405); echo 'Method Not Allowed'; return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data) || ($data['secret'] ?? '') !== self::SECRET_KEY) {
            http_response_code(403); echo 'Forbidden'; return;
        }

        $discord_msg_id = $data['discord_msg_id'] ?? null;
        $title          = substr(trim($data['title']       ?? 'Changelog'), 0, 100);
        $description    = trim($data['description'] ?? '');
        $date           = (int) ($data['date'] ?? time());

        if (!$discord_msg_id || !$description) {
            http_response_code(400); echo 'Missing fields'; return;
        }

        // Évite les doublons
        $exists = $this->db
            ->where('discord_msg_id', $discord_msg_id)
            ->limit(1)
            ->get('changelogs')
            ->row();

        if ($exists) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'already_exists', 'id' => $exists->id]);
            return;
        }

        $this->db->insert('changelogs', [
            'title'          => $title,
            'description'    => $description,
            'date'           => $date,
            'discord_msg_id' => $discord_msg_id,
        ]);

        $id = $this->db->insert_id();
        log_message('info', 'Changelog #' . $id . ' inséré depuis Discord msg ' . $discord_msg_id);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'id' => $id]);
    }

    /* ════════════════════════════════════════════════════
       POST /changelogs/delete
       Appelé par le plugin ModMail quand un message est
       supprimé du canal #changelogs Discord.
    ════════════════════════════════════════════════════ */
    public function delete()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405); echo 'Method Not Allowed'; return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data) || ($data['secret'] ?? '') !== self::SECRET_KEY) {
            http_response_code(403); echo 'Forbidden'; return;
        }

        $discord_msg_id = $data['discord_msg_id'] ?? null;
        if (!$discord_msg_id) {
            http_response_code(400); echo 'Missing discord_msg_id'; return;
        }

        $this->db->where('discord_msg_id', $discord_msg_id)->delete('changelogs');
        $deleted = $this->db->affected_rows();

        log_message('info', 'Changelog supprimé pour Discord msg ' . $discord_msg_id);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'deleted' => $deleted]);
    }
}
