<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Store_cart_model
 *
 * Persistance du panier CodeIgniter entre les sessions.
 * - saveCartForUser()    : appelé au logout, snapshot du panier en BDD
 * - restoreCartForUser() : appelé au login, réinjecte les articles sauvegardés
 * - clearSavedCart()     : appelé après un achat réussi
 *
 * Table requise :
 *   CREATE TABLE `store_cart_saved` (
 *     `userid`     INT UNSIGNED NOT NULL,
 *     `cart_data`  TEXT         NOT NULL,
 *     `updated_at` INT UNSIGNED NOT NULL,
 *     PRIMARY KEY (`userid`)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class Store_cart_model extends CI_Model {

    private $table = 'store_cart_saved';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('cart');
    }

    // ─────────────────────────────────────────────────────────
    //  SAUVEGARDE — appelée au logout
    // ─────────────────────────────────────────────────────────

    /**
     * Sérialise le panier CI courant et le persiste en BDD.
     * Si le panier est vide, supprime l'entrée existante (rien à restaurer).
     *
     * @param int $userid
     * @return void
     */
    public function saveCartForUser($userid)
    {
        $contents = $this->cart->contents();

        // Panier vide → nettoyer toute sauvegarde existante
        if (empty($contents)) {
            $this->clearSavedCart($userid);
            return;
        }

        // On stocke tous les champs utiles pour la restauration
        $items = [];
        foreach ($contents as $rowid => $item) {
            $items[] = [
                'id'           => $item['id'],
                'qty'          => $item['qty'],
                'price'        => $item['price'],
                'name'         => $item['name'],
                'dp'           => isset($item['dp'])           ? $item['dp']           : 0,
                'vp'           => isset($item['vp'])           ? $item['vp']           : 0,
                'guid'         => isset($item['guid'])         ? $item['guid']         : 0,
                'category'     => isset($item['category'])     ? $item['category']     : 0,
                'pricechoosed' => isset($item['pricechoosed']) ? $item['pricechoosed'] : 0,
                'options'      => isset($item['options'])      ? $item['options']      : [],
            ];
        }

        $payload = json_encode($items);

        // INSERT ... ON DUPLICATE KEY UPDATE (userid est PRIMARY KEY)
        $existing = $this->db->where('userid', $userid)->get($this->table)->num_rows();

        if ($existing) {
            $this->db->where('userid', $userid)->update($this->table, [
                'cart_data'  => $payload,
                'updated_at' => time(),
            ]);
        } else {
            $this->db->insert($this->table, [
                'userid'     => $userid,
                'cart_data'  => $payload,
                'updated_at' => time(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────
    //  RESTAURATION — appelée au login
    // ─────────────────────────────────────────────────────────

    /**
     * Recharge les articles sauvegardés dans le panier CI courant,
     * puis supprime l'entrée BDD (évite une double-restauration).
     *
     * @param int $userid
     * @return void
     */
    public function restoreCartForUser($userid)
    {
        $row = $this->db->where('userid', $userid)->get($this->table)->row_array();

        if (empty($row) || empty($row['cart_data'])) {
            return;
        }

        $items = json_decode($row['cart_data'], true);

        if (!is_array($items) || empty($items)) {
            $this->clearSavedCart($userid);
            return;
        }

        foreach ($items as $item) {
            $exists = $this->db
                ->where('id', (int)$item['id'])
                ->count_all_results('store_items');

            if (!$exists) continue;

            // CI Cart refuse price=0 — on force à 1 (valeur fictive, les vrais prix
            // sont dans dp/vp qui sont des champs custom hors validation CI)
            $data = [
                'id'           => $item['id'],
                'qty'          => $item['qty'],
                'price'        => max(1, (int)$item['price']),
                'name'         => $item['name'],
                'dp'           => $item['dp'],
                'vp'           => $item['vp'],
                'guid'         => $item['guid'],
                'category'     => $item['category'],
                'pricechoosed' => $item['pricechoosed'],
                'options'      => !empty($item['options']) ? $item['options'] : ['Key' => uniqid()],
            ];

            $this->cart->insert($data);
        }

        // Supprimer après restauration pour ne pas re-restaurer
        $this->clearSavedCart($userid);
    }

    // ─────────────────────────────────────────────────────────
    //  SUPPRESSION — appelée après un achat
    // ─────────────────────────────────────────────────────────

    /**
     * Supprime la sauvegarde d'un utilisateur.
     * Appelée après checkout réussi pour ne pas restaurer un panier déjà acheté.
     *
     * @param int $userid
     * @return void
     */
    public function clearSavedCart($userid)
    {
        $this->db->where('userid', $userid)->delete($this->table);
    }
}
