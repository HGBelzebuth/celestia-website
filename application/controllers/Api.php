<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        // Supprime le BOM UTF-8 si le fichier a été sauvegardé avec
        if (ob_get_level() && substr(ob_get_contents(), 0, 3) === "\xEF\xBB\xBF") {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($this->input->method() === 'options') {
            http_response_code(204);
            exit;
        }

        $this->_ensureTokensTable();
    }

    /* ── Actualités ────────────────────────────────────────────────────── */

    public function news()
    {
        $rows  = $this->db->select('id, title, description, image, date')
                          ->order_by('id', 'DESC')
                          ->limit(4)
                          ->get('news')
                          ->result();
        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id'          => (int) $row->id,
                'title'       => $row->title,
                'description' => mb_strimwidth($this->_plainText($row->description), 0, 160, '…'),
                'content'     => $row->description,
                'image'       => $row->image ? (strpos($row->image, 'http') === 0 ? $row->image : rtrim(base_url(), '/') . '/' . ltrim($row->image, '/')) : null,
                'date'        => date('j M Y', (int) $row->date),
            ];
        }

        echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /* ── Joueurs en ligne ──────────────────────────────────────────────── */

    public function online()
    {
        $realms = $this->wowrealm->getRealms()->result();
        $total  = 0;

        foreach ($realms as $realm) {
            try {
                $charDb = $this->wowrealm->getRealmConnectionData((int) $realm->id);
                $total += $charDb->where('online', 1)->from('characters')->count_all_results();
            } catch (Exception $e) { // phpcs:ignore
                // realm unreachable
            }
        }

        echo json_encode(['total' => $total]);
    }
    /* ── Liste des joueurs en ligne ─────────────────────────────────────── */

    public function who()
    {
        $allianceRaces = [1, 3, 4, 7, 11];

        // Zone mapping : TrinityCore zone_id → nom FR
        // Ajouter une zone ici ET dans server-side/zones.json du dépôt Git.
        $zones = [
            // Kalimdor
            148  => 'Teldrassil',            130  => 'Île de la Brume aveugle',
            215  => 'Milles Pointes',        331  => 'Ashenvale',
            357  => 'Féralas',               361  => 'Marais de Dustwallow',
            400  => 'Hiverpiquet',           405  => 'Clairière de Lune',
            406  => 'Silithus',              440  => 'Tanaris',
            490  => "Cratère d'Un'Goro",     493  => 'Gangrebois',
            139  => 'Mulgore',               1637 => 'Orgrimmar',
            1657 => 'Darnassus',             3703 => "L'Exodar",
            // Royaumes de l'Est
            1    => 'Dun Morogh',            4    => "Forêt d'Elwynn",
            8    => 'Terres Ingrates',        17   => 'Les Carmines',
            28   => 'Strangleronce',          33   => "Marécage d'Âprefange",
            36   => 'Lande de Sombreterre',  38   => "Marche de l'Ouest",
            40   => 'Bois des Chants éternels', 44 => 'Hautes-terres du Crépuscule',
            46   => "Hautes-terres d'Arathi", 47  => 'Contreforts de Hautebrande',
            51   => 'Gorge des Vents Brûlants', 85 => 'Steppes ardentes',
            267  => 'Collines de Hautebrande', 268 => "Maleterres de l'Ouest",
            281  => 'Pays-creux',             1497 => 'Fossoyeuse',
            1519 => 'Cité de Hurlevent',     1537 => 'Forgefer',
            3487 => "Cité d'Argent-lune",
            // Outland
            3483 => 'Péninsule des Flammes infernales',
            3518 => 'Nagrand',               3519 => 'Forêt de Térrokar',
            3520 => "Vallée d'Ombrecrépuscule", 3521 => 'Marécage de Zangar',
            3522 => 'Chaîne de Lame-tranchante', 3523 => 'Nétherbrume',
            3524 => 'Shattrath',
            // Norfendre
            65   => 'Dragonet',              66   => "Zul'Drak",
            67   => 'Pics Foudroyants',      210  => 'Icecrown',
            394  => 'Forêts du Chant',       495  => 'Fjord Hurlant',
            3537 => 'Dalaran',               4742 => "Joug-d'hiver",
            // Instances
            2437 => 'Gouffre de Ragefeu',    2477 => 'Les Mortemines',
            2557 => 'Geôles de Hurlevent',   2597 => 'Gnomeregan',
            2617 => 'Kraul de Tranchejabot', 2677 => 'Monastère Écarlate',
            2717 => 'Descente de Tranchejabot', 2757 => 'Uldaman',
            2817 => "Zul'Farrak",            2836 => "Temple d'Atal'Hakkar",
            1584 => 'Scholomance',           1583 => 'Stratholme',
            3556 => 'Karazhan',              3606 => "Zul'Aman",
            3711 => 'Le Nexus',              3714 => "Forteresse d'Utgarde",
            4065 => "Pinacle d'Utgarde",     4100 => "L'Oculus",
            4120 => 'Ulduar',                4196 => 'Champion du Tournoi',
            4228 => 'Citadelle de la Couronne de glace',
            4258 => 'La Forge des âmes',     4264 => 'La Fosse de Saron',
            4272 => 'Halls du reflet',
            5189 => 'Uldum',       4273 => 'Le Sanctuaire du Rubis',
            4295 => 'Triage de Stratholme',  4372 => "Cachot d'Archavon",
            4395 => 'Prison violette',        4415 => "Gundrak",
            4421 => 'Halls de la Pierre',    4422 => 'Halls de la Foudre',
            4603 => 'Azjol-Nerub',           4604 => "Ahn'kahet",
            4723 => 'Naxxramas',             4813 => "Sanctuaire d'Obsidienne",
        ];

        $players = [];

        foreach ($this->wowrealm->getRealms()->result() as $realm) {
            try {
                $db   = $this->wowrealm->getRealmConnectionData((int) $realm->id);
                $rows = $db->select('c.name, c.level, c.race, c.zone, COALESCE(g.name, "") AS guild')
                    ->from('characters c')
                    ->join('guild_member gm', 'gm.guid = c.guid', 'left')
                    ->join('guild g',         'g.guildid = gm.guildid', 'left')
                    ->where('c.online', 1)
                    ->order_by('c.level', 'DESC')
                    ->get()->result_array();

                foreach ($rows as $r) {
                    $zoneId    = (int) $r['zone'];
                    $players[] = [
                        'name'    => $r['name'],
                        'level'   => (int) $r['level'],
                        'faction' => in_array((int) $r['race'], $allianceRaces) ? 'Alliance' : 'Horde',
                        'zone'    => isset($zones[$zoneId]) ? $zones[$zoneId] : 'Zone #' . $zoneId,
                        'guild'   => $r['guild'],
                    ];
                }
            } catch (Exception $e) { // phpcs:ignore
                // realm unreachable
            }
        }

        echo json_encode(['players' => $players], JSON_UNESCAPED_UNICODE);
    }


    /* ── Authentification ──────────────────────────────────────────────── */

    public function login()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
            return;
        }

        $username = trim($this->input->post('username', true));
        $password = $this->input->post('password');

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Identifiant et mot de passe requis.']);
            return;
        }

        if (!$this->wowauth->valid_password($username, $password)) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Identifiant ou mot de passe incorrect.']);
            return;
        }

        $userid   = (int) $this->wowauth->getIDAccount($username);
        $username = $this->wowauth->getUsernameID($userid);
        $row_user = $this->db->select('vp, dp')->where('id', $userid)->limit(1)->get('users')->row();
        $vp       = (int) ($row_user->vp ?? 0);
        $dp       = (int) ($row_user->dp ?? 0);
        $gmlevel  = (int) ($this->wowauth->getRank($userid) ?? 0);

        $token     = bin2hex(random_bytes(32));
        $expiresAt = time() + (30 * 24 * 3600);

        $this->db->delete('launcher_sessions', ['userid' => $userid]);
        $this->db->insert('launcher_sessions', [
            'userid'     => $userid,
            'token'      => $token,
            'expires_at' => $expiresAt,
            'gmlevel'    => $gmlevel,
        ]);

        echo json_encode([
            'status'   => 'ok',
            'token'    => $token,
            'username' => $username,
            'vp'       => $vp,
            'dp'       => $dp,
            'gmlevel'  => $gmlevel,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function logout()
    {
        $token = $this->input->post('token', true) ?: $this->input->get('token', true);
        if ($token) {
            $this->db->delete('launcher_sessions', ['token' => $token]);
        }
        echo json_encode(['status' => 'ok']);
    }

    /* ── Votes ─────────────────────────────────────────────────────────── */

    public function votes()
    {
        $token  = $this->input->get('token', true);
        $userid = $this->_getUserFromToken($token);

        if (!$userid) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Session expirée, reconnectez-vous.']);
            return;
        }

        $this->load->model('vote/vote_model');

        $sites   = $this->vote_model->getVotes();
        $voteIds = array_map(fn($s) => (int) $s->id, $sites);
        $cooldowns = $this->vote_model->getTimeLogExpiredBulk($voteIds, $userid);

        $now    = time();
        $result = [];

        foreach ($sites as $site) {
            $endsAt    = isset($cooldowns[(int) $site->id]) ? (int) $cooldowns[(int) $site->id] : null;
            $available = !$endsAt || $now >= $endsAt;

            $result[] = [
                'id'            => (int) $site->id,
                'name'          => $site->name,
                'points'        => (int) $site->points,
                'available'     => $available,
                'cooldown_ends' => (!$available && $endsAt) ? $endsAt : null,
            ];
        }

        $row_user2 = $this->db->select('vp, dp')->where('id', $userid)->limit(1)->get('users')->row();
        $vp = (int) ($row_user2->vp ?? 0);
        $dp = (int) ($row_user2->dp ?? 0);

        echo json_encode([
            'status' => 'ok',
            'vp'     => $vp,
            'dp'     => $dp,
            'sites'  => $result,
        ], JSON_UNESCAPED_UNICODE);
    }

    /* ── Vote : initiation ────────────────────────────────────────────── */

    public function vote_start()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_getUserFromToken($token);
        if (!$userid) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Session expirée.']);
            return;
        }

        $id   = (int) $this->input->post('site_id', true);
        $vote = $this->db->where('id', $id)->where('active', 1)->limit(1)->get('votes')->row();
        if (!$id || !$vote) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Site de vote introuvable.']);
            return;
        }

        $url = (string) $vote->url;
        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'http://' . $url;
        }

        /* Vérification cooldown */
        $expired_at = $this->db
            ->where('idaccount', $userid)->where('idvote', $id)
            ->order_by('id', 'DESC')->limit(1)
            ->get('votes_logs')->row('expired_at');

        if ($expired_at && time() < (int) $expired_at) {
            echo json_encode(['status' => 'cooldown', 'message' => 'Tu as déjà voté sur ce site récemment. Reviens plus tard !']);
            return;
        }

        $api_type = (string) ($vote->api_type ?? '');

        if (!empty($api_type)) {
            /* Vérifie si un vote est déjà en attente (< 30 min) */
            $limit   = time() - 1800;
            $pending = $this->db
                ->where('idaccount', $userid)->where('idvote', $id)
                ->where('created_at >', $limit)->limit(1)
                ->get('votes_pending')->row();

            if ($pending) {
                echo json_encode(['status' => 'pending', 'message' => 'Un vote est déjà en cours de vérification.']);
                return;
            }

            if ($api_type === 'top100arena') {
                $url = rtrim($url, '/') . '?incentive=' . $userid;
            }

            $this->db->where('created_at <', $limit)->delete('votes_pending');
            $this->db->insert('votes_pending', ['idaccount' => $userid, 'idvote' => $id, 'created_at' => time()]);

            echo json_encode(['status' => 'pending_created', 'vote_url' => $url]);
        } else {
            $this->_creditVote($userid, $id, $vote);
            echo json_encode(['status' => 'credited', 'vote_url' => $url]);
        }
    }

    /* ── Vote : vérification ───────────────────────────────────────────── */

    public function vote_verify($id = 0)
    {
        $id     = (int) $id;
        $token  = $this->input->get('token', true);
        $userid = $this->_getUserFromToken($token);
        if (!$userid || !$id) {
            http_response_code(401);
            echo json_encode(['status' => 'error']);
            return;
        }

        $vote     = $this->db->where('id', $id)->limit(1)->get('votes')->row();
        $api_type = $vote ? (string) ($vote->api_type ?? '') : '';
        $limit    = time() - 1800;

        if ($api_type === 'top100arena') {
            $pending = $this->db
                ->where('idaccount', $userid)->where('idvote', $id)
                ->where('created_at >', $limit)->limit(1)
                ->get('votes_pending')->row();
            echo json_encode(['status' => $pending ? 'not_yet' : 'credited']);
            return;
        }

        if ($api_type === 'serveur_prive') {
            $pending = $this->db
                ->where('idaccount', $userid)->where('idvote', $id)
                ->where('created_at >', $limit)->limit(1)
                ->get('votes_pending')->row();
            if (!$pending) { echo json_encode(['status' => 'not_yet']); return; }

            $ch = curl_init('https://serveur-prive.net/api/vote/' . $vote->api_token . '/' . $this->input->ip_address());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            curl_close($ch);

            if (trim($response) !== '1') { echo json_encode(['status' => 'not_yet']); return; }
            $this->_creditVote($userid, $id, $vote);
            echo json_encode(['status' => 'credited']);
            return;
        }

        if ($api_type === 'server_pulse') {
            $pending = $this->db
                ->where('idaccount', $userid)->where('idvote', $id)
                ->where('created_at >', $limit)->limit(1)
                ->get('votes_pending')->row();
            if (!$pending) { echo json_encode(['status' => 'not_yet']); return; }

            $ch = curl_init('https://serveur-prive-impulsion.net/api/servers/' . $vote->api_token . '/vote-status/' . $this->input->ip_address());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $data = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (!is_array($data) || !isset($data['can_vote']) || $data['can_vote'] === true) {
                echo json_encode(['status' => 'not_yet']); return;
            }
            $this->_creditVote($userid, $id, $vote);
            echo json_encode(['status' => 'credited']);
            return;
        }

        echo json_encode(['status' => 'credited']);
    }

    /* ── Admin : création d'actualité ─────────────────────────────────── */

    public function news_create()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $title       = trim($this->input->post('title',       true) ?? '');
        $description = trim($this->input->post('description', false) ?? '');
        $image       = trim($this->input->post('image',       true) ?? '');

        if (!$title || !$description) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Titre et description requis.']);
            return;
        }

        $this->db->insert('news', [
            'title'       => $title,
            'description' => $description,
            'image'       => $image,
            'date'        => time(),
        ]);

        $id = $this->db->insert_id();

        echo json_encode(['status' => 'ok', 'id' => (int) $id], JSON_UNESCAPED_UNICODE);
    }

    /* ── Admin : modification d'actualité ─────────────────────────────── */

    public function news_update()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $id          = (int) ($this->input->post('id', true) ?? 0);
        $title       = trim($this->input->post('title',       true)  ?? '');
        $description = trim($this->input->post('description', false)  ?? '');
        $image       = trim($this->input->post('image',       true)   ?? '');

        if (!$id || !$title || !$description) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'id, titre et description requis.']);
            return;
        }

        $this->db->where('id', $id)->update('news', [
            'title'       => $title,
            'description' => $description,
            'image'       => $image,
        ]);

        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    }

    /* ── Admin : suppression d'une actualité ─────────────────────────── */

    public function news_delete()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $id = (int) ($this->input->post('id', true) ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'id requis.']);
            return;
        }

        $this->db->where('id', $id)->delete('news');

        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    }

    /* ── Admin : clé d'upload patch ───────────────────────────────────── */
    // Le secret est chargé depuis application/config/launcher_secrets.php sur le serveur.
    // Ce fichier n'est PAS dans git — voir server-side/secrets.example.php pour le modèle.

    public function patch_secret()
    {
        $token  = $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        // Charge le secret depuis un fichier non versionné sur le serveur
        $secrets_file = APPPATH . 'config/launcher_secrets.php';
        if (file_exists($secrets_file) && !defined('PATCH_ADMIN_SECRET')) {
            require_once $secrets_file;
        }

        $secret = defined('PATCH_ADMIN_SECRET') ? PATCH_ADMIN_SECRET : '';

        if (!$secret) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Secret non configuré sur le serveur.']);
            return;
        }

        echo json_encode([
            'status' => 'ok',
            'secret' => $secret,
        ]);
    }

    /* ── Changelogs (liste publique) ──────────────────────────────────── */

    public function changelogs()
    {
        $rows  = $this->db->select('id, title, description, date')
                          ->order_by('id', 'DESC')
                          ->limit(30)
                          ->get('changelogs')
                          ->result();
        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id'          => (int) $row->id,
                'title'       => $row->title,
                'description' => mb_strimwidth($this->_plainText($row->description), 0, 160, '…'),
                'content'     => $row->description,
                'date'        => date('j M Y', (int) $row->date),
            ];
        }

        echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /* ── Admin : création de changelog ───────────────────────────────── */

    public function changelogs_create()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $title       = trim($this->input->post('title',       true) ?? '');
        $description = trim($this->input->post('description', false) ?? '');

        if (!$title || !$description) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Titre et description requis.']);
            return;
        }

        $row = ['title' => $title, 'description' => $description, 'date' => time()];
        // discord_msg_id : ajouté si la colonne existe (compatibility)
        if (in_array('discord_msg_id', array_column($this->db->field_data('changelogs'), 'name'))) {
            $row['discord_msg_id'] = null;
        }

        $this->db->insert('changelogs', $row);
        $id = $this->db->insert_id();

        echo json_encode(['status' => 'ok', 'id' => (int) $id], JSON_UNESCAPED_UNICODE);
    }

    /* ── Admin : modification de changelog ───────────────────────────── */

    public function changelogs_update()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $id          = (int)  ($this->input->post('id',          true)  ?? 0);
        $title       = trim(   $this->input->post('title',       true)  ?? '');
        $description = trim(   $this->input->post('description', false) ?? '');

        if (!$id || !$title || !$description) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'id, titre et description requis.']);
            return;
        }

        $this->db->where('id', $id)->update('changelogs', [
            'title'       => $title,
            'description' => $description,
        ]);

        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    }

    /* ── Admin : suppression de changelog ────────────────────────────── */

    public function changelogs_delete()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $id = (int) ($this->input->post('id', true) ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'id requis.']);
            return;
        }

        $this->db->where('id', $id)->delete('changelogs');
        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    }

    /* ── Packs de donation ───────────────────────────────────────────── */

    public function donate_packages()
    {
        $rows = $this->db->select('id, name, price, points')
                         ->order_by('price', 'ASC')
                         ->get('donate')
                         ->result();

        $packages = array_map(fn($r) => [
            'id'     => (int) $r->id,
            'name'   => $r->name,
            'price'  => (float) $r->price,
            'points' => (int) $r->points,
        ], $rows);

        echo json_encode($packages, JSON_UNESCAPED_UNICODE);
    }

    /* ── Offre donation en cours ─────────────────────────────────────── */

    public function donate_offer()
    {
        $this->_ensurePromoTable();

        $row = $this->db->where('id', 1)->limit(1)->get('launcher_donate_offer')->row();

        if (!$row || (int) $row->donate_id === 0) {
            echo json_encode(['offer' => null]);
            return;
        }

        $pack = $this->db->select('name, price, points')
                         ->where('id', $row->donate_id)
                         ->limit(1)
                         ->get('donate')
                         ->row();

        if (!$pack) {
            echo json_encode(['offer' => null]);
            return;
        }

        $hasPromoPrice  = ($row->promo_price !== '') && ((float) $row->promo_price !== (float) $pack->price);
        $hasPromoPoints = ((int) $row->promo_points > 0) && ((int) $row->promo_points !== (int) $pack->points);

        echo json_encode([
            'offer' => [
                'donate_id'    => (int) $row->donate_id,
                'name'         => ($row->promo_name !== '')  ? $row->promo_name  : $pack->name,
                'price'        => ($row->promo_price !== '') ? (float) $row->promo_price : (float) $pack->price,
                'points'       => ((int) $row->promo_points > 0) ? (int) $row->promo_points : (int) $pack->points,
                'base_price'   => (float) $pack->price,
                'base_points'  => (int)   $pack->points,
                'has_discount' => $hasPromoPrice || $hasPromoPoints,
                'message'      => $row->message ?? '',
                // Champs bruts pour le panneau admin
                'promo_name'   => $row->promo_name,
                'promo_price'  => $row->promo_price,
                'promo_points' => (int) $row->promo_points,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /* ── Admin : définir/modifier l'offre donation ───────────────────── */

    public function donate_offer_set()
    {
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true) ?: $this->input->get('token', true);
        $userid = $this->_requireAdmin($token);

        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé ou session expirée.']);
            return;
        }

        $this->_ensurePromoTable();

        $donate_id    = (int)  ($this->input->post('donate_id',    true) ?? 0);
        $promo_name   = trim(   $this->input->post('promo_name',   true) ?? '');
        $promo_price  = trim(   $this->input->post('promo_price',  true) ?? '');
        $promo_points = (int)  ($this->input->post('promo_points', true) ?? 0);
        $message      = trim(   $this->input->post('message',      true) ?? '');

        $data = [
            'donate_id'    => $donate_id,
            'promo_name'   => $promo_name,
            'promo_price'  => $promo_price,
            'promo_points' => $promo_points,
            'message'      => $message,
        ];

        $exists = $this->db->where('id', 1)->count_all_results('launcher_donate_offer');
        if ($exists) {
            $this->db->where('id', 1)->update('launcher_donate_offer', $data);
        } else {
            $this->db->insert('launcher_donate_offer', array_merge(['id' => 1], $data));
        }

        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    }

    /* ── Helpers privés ────────────────────────────────────────────────── */

    private function _creditVote(int $userid, int $voteid, object $vote): void
    {
        $points     = (int) $vote->points;
        $expired_at = time() + ((int) $vote->time * 60);

        $this->db->set('vp', 'vp + ' . $points, false)->where('id', $userid)->update('users');
        $this->db->insert('votes_logs', [
            'idaccount'  => $userid,
            'idvote'     => $voteid,
            'lasttime'   => time(),
            'expired_at' => $expired_at,
            'points'     => $points,
            'ip'         => $this->input->ip_address(),
        ]);
        $this->db->where('idaccount', $userid)->where('idvote', $voteid)->delete('votes_pending');
    }

    private function _getUserFromToken(?string $token): int
    {
        if (!$token) return 0;

        $row = $this->db
            ->where('token', $token)
            ->where('expires_at >', time())
            ->limit(1)
            ->get('launcher_sessions')
            ->row();

        return $row ? (int) $row->userid : 0;
    }

    /**
     * Retourne le userid si le token est valide ET gmlevel >= 1, sinon 0.
     * Lit gmlevel depuis launcher_sessions (stocké au login) — aucun appel modèle.
     */
    private function _requireAdmin(?string $token): int
    {
        if (!$token) return 0;

        $row = $this->db
            ->where('token', $token)
            ->where('expires_at >', time())
            ->limit(1)
            ->get('launcher_sessions')
            ->row();

        if (!$row) return 0;
        return ((int) ($row->gmlevel ?? 0)) >= 1 ? (int) $row->userid : 0;
    }

    private function _ensureTokensTable(): void
    {
        if (!$this->db->table_exists('launcher_sessions')) {
            $this->load->dbforge();
            $this->dbforge->add_field([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'userid'     => ['type' => 'INT', 'unsigned' => true],
                'token'      => ['type' => 'VARCHAR', 'constraint' => 64],
                'expires_at' => ['type' => 'INT', 'unsigned' => true],
                'gmlevel'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('token');
            $this->dbforge->create_table('launcher_sessions');
        } else {
            // Migration silencieuse : ajoute gmlevel si la colonne est absente
            $columns = array_column($this->db->field_data('launcher_sessions'), 'name');
            if (!in_array('gmlevel', $columns)) {
                $this->load->dbforge();
                $this->dbforge->add_column('launcher_sessions', [
                    'gmlevel' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                ]);
            }
        }
    }

    // ── Promo boutique ───────────────────────────────────────────────────────

    public function store_promo_reset()
    {
        header('Content-Type: application/json');

        $token  = $this->input->post('token', true);
        $userid = $this->_requireAdmin($token);
        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
            return;
        }

        $root = $_SERVER['DOCUMENT_ROOT'] ?? FCPATH;
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wowstore_' . substr(md5($root), 0, 8) . '_';

        $promoCache = @json_decode(@file_get_contents($base . 'store_promo.json'), true);
        if (is_array($promoCache) && !empty($promoCache['promo_injected_top_id'])) {
            $this->db->where('id', (int)$promoCache['promo_injected_top_id'])->delete('store_top');
        }

        @unlink($base . 'store_promo.json');

        echo json_encode(['status' => 'ok', 'message' => 'Cache promo supprimé. Le prochain tirage sera effectué à la prochaine visite de la boutique.']);
    }

    public function store_promo()
    {
        header('Content-Type: application/json');

        // Délègue entièrement au Store_model qui gère le chemin de cache correctement
        // (substr(md5(DOCUMENT_ROOT), 0, 8) — différent de md5(FCPATH))
        $this->load->model('store/store_model', 'storeModel');
        $promo = $this->storeModel->getPromoItem();

        if (!$promo) {
            echo json_encode(['promo' => null]);
            return;
        }

        $item = $this->db->select('icon')
                         ->where('id', $promo['item_id'])
                         ->limit(1)
                         ->get('store_items')
                         ->row();

        echo json_encode([
            'promo' => [
                'item_id'    => $promo['item_id'],
                'item_name'  => $promo['item_name'],
                'icon'       => ($item && $item->icon !== '') ? $item->icon : null,
                'discount'   => $promo['discount'],
                'expires_at' => $promo['expires_at'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // ── Notifications ────────────────────────────────────────────────────────

    public function notifications()
    {
        header('Content-Type: application/json');

        $token  = $this->input->get('token', true);
        $userid = $this->_getUserFromToken($token);
        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Non autorisé.']);
            return;
        }

        $this->_ensureSeenMailsTable();
        $db_chars = $this->load->database('characters', TRUE);

        // 1. Personnages + or
        $has_money  = $db_chars->field_exists('money', 'characters');
        $sel_cols   = $has_money ? 'guid, name, money' : 'guid, name';
        $chars      = $db_chars->select($sel_cols)->where('account', $userid)->get('characters')->result();

        $char_guids   = [];
        $gold_chars   = [];
        $total_copper = 0;
        foreach ($chars as $c) {
            $char_guids[] = (int) $c->guid;
            $copper        = isset($c->money) ? (int) $c->money : 0;
            $total_copper += $copper;
            $gold_chars[]  = ['name' => $c->name, 'copper' => $copper];
        }

        // 2. Mails AH (30 plus récents)
        $ah_sales = [];
        if (!empty($char_guids)) {
            $seen_ids = array_map('intval', array_column(
                $this->db->select('mail_id')->where('userid', $userid)->get('launcher_seen_mails')->result_array(),
                'mail_id'
            ));

            $has_items_col = $db_chars->field_exists('has_items', 'mail');
            $mail_select   = $has_items_col
                ? 'id, subject, money, deliver_time, has_items'
                : 'id, subject, money, deliver_time';

            $mails = $db_chars->select($mail_select)
                              ->where('messageType', 2)
                              ->where_in('receiver', $char_guids)
                              ->order_by('id', 'DESC')
                              ->limit(30)
                              ->get('mail')
                              ->result();

            foreach ($mails as $m) {
                $copper    = (int) $m->money;
                $has_items = $has_items_col ? (int) $m->has_items : 0;
                if ($copper > 0) {
                    $mail_type = 2; // vendu
                } elseif ($has_items > 0) {
                    $mail_type = 1; // retourné / article reçu
                } else {
                    $mail_type = 1;
                }
                $ah_sales[] = [
                    'id'        => (int) $m->id,
                    'item_name' => $m->subject ?: 'Article',
                    'mail_type' => $mail_type,
                    'gold'      => $copper,
                    'timestamp' => (int) $m->deliver_time,
                    'unseen'    => !in_array((int) $m->id, $seen_ids),
                ];
            }
        }

        // Epargne Celestia
        $savings = [];
        try {
            $db_eluna    = $this->load->database('eluna', TRUE);
            $saving_rows = $db_eluna->where('account_id', $userid)->get('PlanEpargneCelestia')->result();
            foreach ($saving_rows as $s) {
                $maturity_ts  = (int) strtotime($s->deposit_end_time);
                $is_available = $maturity_ts <= time();
                $savings[] = [
                    'char_name'    => $s->player_name,
                    'amount'       => (int) $s->amount_deposited,
                    'interest'     => (int) $s->interests,
                    'total'        => (int) $s->amount_deposited + (int) $s->interests,
                    'maturity_ts'  => $maturity_ts,
                    'is_available' => $is_available,
                ];
            }
        } catch (Exception $e) {}

        echo json_encode([
            'status'               => 'ok',
            'gold'                 => ['total_copper' => $total_copper, 'characters' => $gold_chars],
            'raid_reset'           => $this->_calcRaidReset(),
            'ah_sales'             => $ah_sales,
            'profession_cooldowns' => [],
            'savings'              => $savings,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function notifications_read()
    {
        header('Content-Type: application/json');

        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST requis.']);
            return;
        }

        $token  = $this->input->post('token', true);
        $userid = $this->_getUserFromToken($token);
        if (!$userid) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Non autorisé.']);
            return;
        }

        $this->_ensureSeenMailsTable();

        $raw      = $this->input->post('mail_ids', true) ?? '';
        $mail_ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
        if (empty($mail_ids)) {
            echo json_encode(['status' => 'ok']);
            return;
        }

        // Vérifier que les mails appartiennent bien à un perso du compte
        $db_chars   = $this->load->database('characters', TRUE);
        $char_guids = array_map('intval', array_column(
            $db_chars->select('guid')->where('account', $userid)->get('characters')->result_array(),
            'guid'
        ));

        if (empty($char_guids)) {
            echo json_encode(['status' => 'ok']);
            return;
        }

        $valid_ids = array_column(
            $db_chars->select('id')
                     ->where_in('id', $mail_ids)
                     ->where_in('receiver', $char_guids)
                     ->get('mail')
                     ->result_array(),
            'id'
        );

        foreach ($valid_ids as $mid) {
            $mid    = (int) $mid;
            $exists = $this->db->where('userid', $userid)->where('mail_id', $mid)
                               ->count_all_results('launcher_seen_mails');
            if (!$exists) {
                $this->db->insert('launcher_seen_mails', ['userid' => $userid, 'mail_id' => $mid]);
            }
        }

        echo json_encode(['status' => 'ok']);
    }

    private function _calcRaidReset(): array
    {
        // Cycle 3 jours à 18h00 heure serveur — référence : 6 jan 2020 18:00
        $ref   = mktime(18, 0, 0, 1, 6, 2020);
        $cycle = 3 * 24 * 3600;
        $now   = time();
        $done  = (int) floor(($now - $ref) / $cycle);
        $next  = $ref + ($done + 1) * $cycle;
        return [
            'seconds_remaining' => max(0, $next - $now),
            'next_reset_ts'     => $next,
        ];
    }

    private function _ensureSeenMailsTable(): void
    {
        if (!$this->db->table_exists('launcher_seen_mails')) {
            $this->load->dbforge();
            $this->dbforge->add_field([
                'id'      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'userid'  => ['type' => 'INT', 'unsigned' => true],
                'mail_id' => ['type' => 'INT', 'unsigned' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['userid', 'mail_id']);
            $this->dbforge->create_table('launcher_seen_mails', true);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function _ensurePromoTable(): void
    {
        if (!$this->db->table_exists('launcher_donate_offer')) {
            $this->load->dbforge();
            $this->dbforge->add_field([
                'id'           => ['type' => 'INT', 'unsigned' => true],
                'donate_id'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'promo_name'   => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
                'promo_price'  => ['type' => 'VARCHAR', 'constraint' => 10,  'default' => ''],
                'promo_points' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'message'      => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('launcher_donate_offer');
        } else {
            // Migration silencieuse : ajoute les colonnes manquantes si table existante incomplète
            $cols = array_column($this->db->field_data('launcher_donate_offer'), 'name');
            $this->load->dbforge();
            if (!in_array('promo_name', $cols)) {
                $this->dbforge->add_column('launcher_donate_offer', [
                    'promo_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
                ]);
            }
            if (!in_array('promo_price', $cols)) {
                $this->dbforge->add_column('launcher_donate_offer', [
                    'promo_price' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => ''],
                ]);
            }
            if (!in_array('promo_points', $cols)) {
                $this->dbforge->add_column('launcher_donate_offer', [
                    'promo_points' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                ]);
            }
        }
    }
    /* ── Parrainage (launcher) ────────────────────────────────────────────── */

    public function referral()
    {
        // Garantir une réponse JSON pure — capturer toute sortie HTML parasite (erreurs PHP/CI)
        @ini_set('display_errors', '0');
        @error_reporting(0);
        ob_start();
        $this->db->db_debug = FALSE;

        $token  = $this->input->get('token', true) ?: $this->input->post('token', true);
        $userid = $this->_getUserFromToken($token);

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        if (!$userid) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Session expirée, reconnectez-vous.']);
            return;
        }

        ob_start();
        $userRow  = $this->db->select('username')->where('id', $userid)->limit(1)->get('users')->row();
        ob_end_clean();

        $username = $userRow ? $userRow->username : null;

        if (!$username) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Utilisateur introuvable.']);
            return;
        }

        $action = $this->input->get('action', true) ?: $this->input->post('action', true);
        if ($action === 'create') {
            $this->_referralCreate($username);
            return;
        }

        $this->_referralGet($username);
    }

    private function _referralConfig(): array
    {
        $row = $this->db->limit(1)->get('referral_config')->row();
        return [
            'max_per_day'   => ($row && (int) $row->max_tokens_per_day > 0) ? (int) $row->max_tokens_per_day : 5,
            'validity_days' => ($row && (int) $row->token_validity_days > 0) ? (int) $row->token_validity_days : 30,
        ];
    }

    private function _referralGet(string $username): void
    {
        $this->db->db_debug = FALSE;
        $cfg = $this->_referralConfig();

        // Tous les tokens actifs (generated + used), du plus recent
        $rows = $this->db
            ->where('sponsor_username', $username)
            ->where_in('token_status', ['generated', 'used'])
            ->order_by('id', 'DESC')
            ->get('referral_tokens')
            ->result();

        $tokens = [];
        foreach ($rows as $r) {
            $tokens[] = [
                'token'   => $r->token,
                'link'    => 'https://celestia-wow.com/inscription?ref=' . $r->token,
                'status'  => $r->token_status,
                'referee' => isset($r->referee_username) ? $r->referee_username : null,
            ];
        }

        // Tokens crees aujourd'hui (limite journaliere)
        $todayCount = (int) $this->db
            ->where('sponsor_username', $username)
            ->where('DATE(created_at)', date('Y-m-d'))
            ->count_all_results('referral_tokens');

        // Parrain de cet utilisateur (s'il est filleul)
        $sponsorRow = $this->db
            ->select('sponsor_username')
            ->where('referee_username', $username)
            ->limit(1)
            ->get('referral_tokens')
            ->row();

        echo json_encode([
            'tokens'     => $tokens,
            'sponsor'    => $sponsorRow ? $sponsorRow->sponsor_username : null,
            'can_create' => $todayCount < $cfg['max_per_day'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function _referralCreate(string $username): void
    {
        $this->db->db_debug = FALSE;
        $cfg = $this->_referralConfig();

        $todayCount = (int) $this->db
            ->where('sponsor_username', $username)
            ->where('DATE(created_at)', date('Y-m-d'))
            ->count_all_results('referral_tokens');

        if ($todayCount >= $cfg['max_per_day']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Limite de ' . $cfg['max_per_day'] . ' codes par jour atteinte.']);
            return;
        }

        do {
            $newToken = strtoupper(bin2hex(random_bytes(4)));
            $exists   = (int) $this->db->where('token', $newToken)->count_all_results('referral_tokens');
        } while ($exists > 0);

        $row = [
            'token'            => $newToken,
            'sponsor_username' => $username,
            'token_status'     => 'generated',
            'referral_status'  => 'pending',
            'created_at'       => date('Y-m-d H:i:s'),
            'expires_at'       => date('Y-m-d H:i:s', strtotime('+' . $cfg['validity_days'] . ' days')),
        ];

        $inserted = $this->db->insert('referral_tokens', $row);

        if (!$inserted) {
            http_response_code(500);
            $dbErr = $this->db->error();
            echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . ($dbErr['message'] ?? 'inconnue')]);
            return;
        }

        echo json_encode([
            'success' => true,
            'token'   => $newToken,
            'link'    => 'https://celestia-wow.com/inscription?ref=' . $newToken,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function _plainText($text) {
        $text = preg_replace('/<style[\s\S]*?<\/style>/i', '', $text);
        $text = preg_replace('/<script[\s\S]*?<\/script>/i', '', $text);
        $text = strip_tags($text);
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/s', '$1', $text);
        $text = preg_replace('/\*([^*]+)\*/s', '$1', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        $text = preg_replace('/^[-*+]\s+/m', '', $text);
        $text = preg_replace('/^>\s*/m', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

}
