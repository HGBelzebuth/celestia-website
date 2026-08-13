<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class armory extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('armory_model');
    }

    public function search()
    {
        $data = array(
            'pagetitle' => 'Armory Search',
            'lang'      => $this->lang->lang(),
            'realms'    => $this->wowrealm->getRealms()->result()
        );
        $this->template->build('search', $data);
    }

    public function index($id)
    {
        if (empty($id) || is_null($id))
            redirect(base_url(), 'refresh');

        $data = array(
            'id'        => $id,
            'pagetitle' => 'Player Armory',
            'lang'      => $this->lang->lang(),
            'realms'    => $this->wowrealm->getRealms()->result()
        );
        $this->template->build('index', $data);
    }

    public function guild($guildid)
    {
        if (empty($guildid) || is_null($guildid))
            redirect(base_url(), 'refresh');

        $data = array(
            'guildid'   => $guildid,
            'pagetitle' => 'Guild Members',
            'lang'      => $this->lang->lang(),
            'realms'    => $this->wowrealm->getRealms()->result()
        );
        $this->template->build('guild', $data);
    }

    public function tooltip($itemid = 0)
    {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=86400');

        $itemid = (int) $itemid;
        if (!$itemid) {
            echo json_encode(['tooltip' => '<b>Item inconnu</b>']);
            return;
        }

        $cacheDir  = APPPATH . 'cache/armory_tooltips/';
        $cacheFile = $cacheDir . $itemid . '.json';

        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            echo file_get_contents($cacheFile);
            return;
        }

        $url = 'https://wotlk.wowhead.com/tooltip/item/' . $itemid;
        $ctx = stream_context_create(['http' => [
            'timeout' => 5,
            'header'  => 'User-Agent: Mozilla/5.0'
        ]]);
        $response = @file_get_contents($url, false, $ctx);

        if ($response) {
            file_put_contents($cacheFile, $response);
            echo $response;
        } else {
            $row = $this->db->query("SELECT name FROM R1_World.item_template WHERE entry = ?", [$itemid])->row();
            $fallback = json_encode(['tooltip' => '<b>' . ($row ? htmlspecialchars($row->name) : 'Item #' . $itemid) . '</b>']);
            echo $fallback;
        }
    }

    public function icon($realmid = 1, $itemid = 0)
    {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=86400');

        $itemid  = (int) $itemid;
        $realmid = (int) $realmid;

        if (!$itemid) {
            echo json_encode(['icon' => 'inv_misc_questionmark']);
            return;
        }

        $url  = 'https://wotlk.evowow.com/?item=' . $itemid . '&xml';
        $ctx  = stream_context_create(['http' => ['timeout' => 3]]);
        $xml  = @file_get_contents($url, false, $ctx);
        $icon = 'inv_misc_questionmark';

        if ($xml) {
            $match = [];
            if (preg_match('/<icon[^>]*>([^<]+)<\/icon>/i', $xml, $match)) {
                $icon = strtolower(trim($match[1]));
            }
        }

        echo json_encode(['icon' => $icon]);
    }

    /**
     * JSON : données complètes d'un item pour le tooltip custom (stats + spells).
     * URL : /armory/item_data/{realmid}/{itemid}
     */
    public function item_data($realmid = 1, $itemid = 0)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        $itemid  = (int) $itemid;
        $realmid = (int) $realmid;

        if (!$itemid) {
            echo json_encode(['error' => 'invalid']);
            return;
        }

        $data = $this->armory_model->getItemTooltipData($itemid, $realmid);

        if (!$data) {
            echo json_encode(['error' => 'not_found']);
            return;
        }

        echo json_encode($data);
    }

    public function result()
    {
        $data = array(
            'pagetitle' => 'Armory Search',
            'lang'      => $this->lang->lang(),
            'realms'    => $this->wowrealm->getRealms()->result(),
            'search'    => $_GET['search'] ?? ''
        );
        $this->template->build('result', $data);
    }
}
