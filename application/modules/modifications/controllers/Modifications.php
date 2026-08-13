<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Modifications extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // On charge notre tout nouveau modèle !
        $this->load->model('Modifications_model');

        if (!ini_get('date.timezone'))
            date_default_timezone_set($this->config->item('timezone'));

        if (!$this->wowgeneral->getMaintenance())
            redirect(base_url('maintenance'), 'refresh');
    }

    // ── ACCUEIL (Catégories) ──────────────────────────────────
    public function index()
    {
        $data = array(
            'pagetitle'  => 'Modifications du serveur',
            'categories' => $this->_getCategoriesHome(),
        );
        $this->template->build('home', $data);
    }

    // ── RAIDS ─────────────────────────────────────────────────
    public function raids()
    {
        $data = array(
            'pagetitle' => 'Modifications — Raids',
            'raids'     => $this->Modifications_model->getPagesByType('raid'),
        );
        $this->template->build('raids', $data);
    }

    public function raid($lang = '', $slug = '')
    {
        if (empty($slug)) { $slug = $lang; $lang = ''; }

        // Appel direct à la base de données !
        $raidData = $this->Modifications_model->getPageBySlug($slug);

        if (!$raidData)
            redirect(base_url('modifications/raids'), 'refresh');

        $data = array(
            'pagetitle' => 'Modifications — ' . $raidData['name'],
            'raid'      => $raidData,
            'slug'      => $slug,
        );
        $this->template->build('raid', $data);
    }

    // ── SERVEUR ───────────────────────────────────────────────
    public function serveur()
    {
        $data = array(
            'pagetitle' => 'Modifications — Serveur',
            'sections'  => $this->Modifications_model->getPagesByType('server'),
        );
        $this->template->build('serveur', $data);
    }

    public function commandes()
    {
        $pageData = $this->Modifications_model->getPageBySlug('commandes');
        $data = array(
            'pagetitle' => 'Modifications — Commandes',
            'sections'  => $pageData ? $pageData['sections'] : array(),
        );
        $this->template->build('commandes', $data);
    }

    public function taux()
    {
        $pageData = $this->Modifications_model->getPageBySlug('taux');
        $data = array(
            'pagetitle' => 'Modifications — Taux',
            'sections'  => $pageData ? $pageData['sections'] : array(),
        );
        $this->template->build('taux', $data);
    }

    public function informations()
    {
        $pageData = $this->Modifications_model->getPageBySlug('informations');
        $data = array(
            'pagetitle' => 'Modifications — Informations',
            'sections'  => $pageData ? $pageData['sections'] : array(),
        );
        $this->template->build('informations', $data);
    }

    // ── CONFIGURATION ACCUEIL (Les 3 grosses cartes) ──────────
    private function _getCategoriesHome()
    {
        // On compte les raids et les pages serveurs pour les petits badges
        $raids = $this->Modifications_model->getPagesByType('raid');
        $modifiedRaidsCount = 0;
        foreach ($raids as $r) { if ($r['has_changes']) $modifiedRaidsCount++; }

        $servers = $this->Modifications_model->getPagesByType('server');
        
        return array(
            array(
                'slug'        => 'raids',
                'name'        => 'Raids',
                'description' => 'Ajustements des boss, mécaniques et difficulté des raids.',
                'image'       => 'https://wow.zamimg.com/uploads/screenshots/normal/148161.jpg',
                'url'         => 'modifications/raids',
                'count'       => $modifiedRaidsCount,
                'label'       => 'modifié',
                'icon'        => 'fas fa-dragon',
                'available'   => true,
            ),
            array(
                'slug'        => 'donjons',
                'name'        => 'Donjons',
                'description' => 'Modifications des donjons héroïques et normaux.',
                'image'       => 'https://wow.zamimg.com/uploads/screenshots/normal/17115.jpg',
                'url'         => 'modifications/donjons',
                'count'       => 0,
                'label'       => 'modifié',
                'icon'        => 'fas fa-dungeon',
                'available'   => false,
            ),
            array(
                'slug'        => 'serveur',
                'name'        => 'Serveur',
                'description' => 'Règles, taux et paramètres généraux du serveur.',
                'image'       => 'https://wow.zamimg.com/uploads/screenshots/normal/374073.jpg',
                'url'         => 'modifications/serveur',
                'count'       => count($servers),
                'label'       => 'section',
                'icon'        => 'fas fa-server',
                'available'   => true,
            ),
        );
    }
}