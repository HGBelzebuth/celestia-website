<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Modifications_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Récupère toutes les pages d'un type précis (ex: 'raid' ou 'server')
     * Utilisé pour la liste des raids (raids.php) et la liste serveur (serveur.php)
     */
    public function getPagesByType($type)
    {
        $this->db->where('page_type', $type);
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get('mod_pages');
        
        $pages = array();
        foreach ($query->result() as $row) {
            $pages[$row->slug] = array(
                'id'          => $row->id,
                'name'        => $row->title,
                'short'       => $row->short_name,
                'image'       => $row->image,
                'description' => $row->description,
                'icon'        => $row->icon,
                'has_changes' => (bool)$row->has_changes,
                'available'   => (bool)$row->is_active,
                // Adapte l'URL selon si c'est un raid ou une page serveur
                'url'         => 'modifications/' . ($type === 'raid' ? 'raid/' : 'serveur/') . $row->slug
            );
        }
        return $pages;
    }

    /**
     * Récupère TOUT le contenu d'une page précise (Raid ou Page Serveur)
     * Formate les données pour reproduire l'ancien tableau statique.
     */
    public function getPageBySlug($slug)
    {
        // 1. On récupère la page principale
        $page = $this->db->get_where('mod_pages', array('slug' => $slug))->row_array();
        if (!$page) return false;

        $result = array(
            'id'          => $page['id'],
            'name'        => $page['title'],
            'short'       => $page['short_name'],
            'image'       => $page['image'],
            'description' => $page['description'],
            'icon'        => $page['icon'],
            'has_changes' => (bool)$page['has_changes'],
            'available'   => (bool)$page['is_active']
        );

        $sections = array();

        // 2. On récupère les catégories (Les Boss, ou les rubriques)
        $this->db->order_by('sort_order', 'ASC');
        $categoriesQuery = $this->db->get_where('mod_categories', array('page_id' => $page['id']))->result_array();

        foreach ($categoriesQuery as $cat) {
            
            // 3. On récupère les lignes de texte (Les changements)
            $this->db->order_by('sort_order', 'ASC');
            $entriesQuery = $this->db->get_where('mod_entries', array('category_id' => $cat['id']))->result_array();
            
            $entries = array();
            foreach ($entriesQuery as $ent) {
                $entries[] = array(
                    'label' => $ent['label'],
                    'rows'  => json_decode($ent['text_rows'], true) ?: array() // On transforme le JSON en tableau PHP
                );
            }

            // On formate le bloc
            $sections[] = array(
                'name'     => $cat['title'],
                'icon'     => $cat['icon_fa'],
                'wow_icon' => $cat['icon_wow'],
                'bg'       => $cat['image_bg'],
                'image'    => $cat['image_bg'], // Utilisé par les pages serveur
                'changes'  => $entries,         // Utilisé par les pages raid
                'entries'  => $entries          // Utilisé par les pages serveur
            );
        }

        // On assigne le tableau final sous les deux noms possibles pour que toutes tes vues fonctionnent direct !
        $result['bosses']   = $sections; 
        $result['sections'] = $sections;

        return $result;
    }
}