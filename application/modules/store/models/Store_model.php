<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Store_model — Optimisations :
 * - Suppression de send_multiple_gifts() dupliquée (appartient au Controller)
 * - Ajout de getItemBatch() : récupère plusieurs items en une seule requête IN(...)
 * - Suppression des error_log() de debug laissés dans l'index du controller (Store.php)
 * - Les méthodes get* individuelles sont conservées pour la compatibilité
 */
class Store_model extends CI_Model {

    protected $top;
    protected $item;
    protected $categories;
    protected $char_db;

    public function __construct()
    {
        $this->top        = 'store_top';
        $this->item       = 'store_items';
        $this->categories = 'store_categories';
        $this->char_db    = $this->load->database('characters', TRUE);
    }

    private function _charDb()
    {
        return $this->char_db;
    }

    // ── Accès items ──────────────────────────────────────────

    public function getItem($id)
    {
        return $this->db->select('*')->where('id', $id)->get($this->item)->row_array();
    }

    /**
     * Récupère plusieurs items en une seule requête.
     * Retourne un tableau indexé par id : ['123' => [...], '456' => [...]]
     *
     * @param  int[] $ids
     * @return array
     */
    public function getItemBatch(array $ids)
    {
        if (empty($ids)) return [];
        $rows   = $this->db->select('*')->where_in('id', $ids)->get($this->item)->result_array();
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['id']] = $row;
        }
        return $result;
    }

    public function getStoreTop()
    {
        return $this->db->select('*')->order_by('id', 'ASC')->limit(50)->get($this->top)->result();
    }

    public function getName($id)
    {
        $name = $this->db->select('name')->where('id', $id)->get($this->item)->row('name');
        return addslashes($name);
    }
    public function getDescription($id)
    {
        return $this->db->select('description')->where('id', $id)->get($this->item)->row('description');
    }

    public function getCategory($id)
    {
        return $this->db->select('category')->where('id', $id)->get($this->item)->row('category');
    }

    public function getType($id)
    {
        return $this->db->select('type')->where('id', $id)->get($this->item)->row('type');
    }

    public function getPriceType($id)
    {
        return $this->db->select('price_type')->where('id', $id)->get($this->item)->row('price_type');
    }

    public function getPriceDP($id)
    {
        return $this->db->select('dp')->where('id', $id)->get($this->item)->row('dp');
    }

    public function getPriceVP($id)
    {
        return $this->db->select('vp')->where('id', $id)->get($this->item)->row('vp');
    }

    public function getWoWhead($id)
    {
        return $this->db->select('wowhead')->where('id', $id)->get($this->item)->row('wowhead');
    }

    public function getIcon($id)
    {
        return $this->db->select('icon')->where('id', $id)->get($this->item)->row('icon');
    }

    public function getCommand($id)
    {
        return $this->db->select('command')->where('id', $id)->get($this->item)->row('command');
    }

    public function getItemExist($id)
    {
        return $this->db->select('*')->where('id', $id)->get($this->item)->num_rows();
    }

    // ── Accès catégories ─────────────────────────────────────

    public function getRoute($id)
    {
        return $this->db->select('route')->where('id', $id)->get($this->categories)->row('route');
    }

    public function getCategoryExist($route)
    {
        return $this->db->select('route')->where('route', $route)->get($this->categories)->num_rows();
    }

    public function getCategoryId($route)
    {
        return $this->db->select('id')->where('route', $route)->get($this->categories)->row('id');
    }

    public function getCategoryName($route)
    {
        return $this->db->select('name')->where('route', $route)->get($this->categories)->row('name');
    }

    public function getCategoryRealm($route)
    {
        return $this->db->select('realmid')->where('route', $route)->get($this->categories)->row('realmid');
    }

    public function getCategoryRealmId($category)
    {
        return $this->db->select('realmid')->where('id', $category)->get($this->categories)->row('realmid');
    }

    public function getCategoryItems($route)
    {
        $id = $this->getCategoryId($route);
        return $this->db->select('*')->where('category', $id)->get($this->item)->result();
    }

    public function getCategories($realmid)
    {
        return $this->db->select('*')->where('realmid', $realmid)->get($this->categories);
    }

    public function getChildStoreCategory($father_id)
    {
        return $this->db->select('*')->where('father', $father_id)->get($this->categories);
    }

    // ── Logs ─────────────────────────────────────────────────

    public function insertStoreLog($accountid, $charid, $name, $type, $pricetype, $dp, $vp)
    {
        $this->db->insert('store_logs', [
            'accountid'  => $accountid,
            'charid'     => $charid,
            'item_name'  => $name,
            'type'       => $type,
            'price_type' => $pricetype,
            'dp'         => $dp,
            'vp'         => $vp,
            'date'       => $this->wowgeneral->getTimestamp(),
        ]);
    }

    // ── Achat ────────────────────────────────────────────────

    public function PurchaseItem($id, $charid, $pricechoosed, $cartDp = null, $cartVp = null)
    {
        $accountid = $this->session->userdata('wow_sess_id');
        $item      = $this->getItem($id);
        $realm     = $this->getCategoryRealmId($item['category']);
        $info      = $this->wowrealm->getRealm($realm)->row_array();

        $dbDp   = (int)$item['dp'];
        $dbVp   = (int)$item['vp'];
        $cartDp = ($cartDp !== null) ? (int)$cartDp : null;
        $cartVp = ($cartVp !== null) ? (int)$cartVp : null;

        if ($pricechoosed != 0) {
            if ($pricechoosed == 1) { $dpprice = ($cartDp !== null) ? $cartDp : $dbDp; $vpprice = 0; }
            else                    { $dpprice = 0; $vpprice = ($cartVp !== null) ? $cartVp : $dbVp; }
        } else {
            if ($item['price_type'] == 1)      { $dpprice = ($cartDp !== null) ? $cartDp : $dbDp; $vpprice = 0; }
            elseif ($item['price_type'] == 2)  { $dpprice = 0; $vpprice = ($cartVp !== null) ? $cartVp : $dbVp; }
            else                               { $dpprice = ($cartDp !== null) ? $cartDp : $dbDp; $vpprice = ($cartVp !== null) ? $cartVp : $dbVp; }
        }

        $multirealm = $this->wowrealm->getRealmConnectionData($realm);
        $charname   = $this->wowrealm->getNameCharacterSpecifyGuid($multirealm, $charid);
        $charexist  = $this->wowrealm->getCharExistGuid($multirealm, $charid);
        $charcheck  = $this->wowrealm->getAccountCharGuid($multirealm, $charid);
        $subject    = $this->lang->line('soap_send_subject');
        $message    = $this->lang->line('soap_send_body');

        if ($charexist < 1 || $charcheck != $accountid) return false;

        $this->_sendSoapCommand($item, $charname, $subject, $message, $info);

        if ($item['price_type'] == 1) {
            $this->db->query("UPDATE users SET dp = (dp-$dpprice) WHERE id = $accountid");
            $this->insertStoreLog($accountid, $charid, $item['name'], $item['type'], $item['price_type'], $dpprice, '0');
        } elseif ($item['price_type'] == 2) {
            $this->db->query("UPDATE users SET vp = (vp-$vpprice) WHERE id = $accountid");
            $this->insertStoreLog($accountid, $charid, $item['name'], $item['type'], $item['price_type'], '0', $vpprice);
        } elseif ($item['price_type'] == 3 || $item['price_type'] == 4) {
            $this->db->query("UPDATE users SET dp = (dp-$dpprice), vp = (vp-$vpprice) WHERE id = $accountid");
            $this->insertStoreLog($accountid, $charid, $item['name'], $item['type'], $item['price_type'], $dpprice, $vpprice);
        } else {
            return false;
        }

        return true;
    }

    public function GiftItem($id, $targetName, $pricechoosed = 0, $cartDp = null, $cartVp = null)
    {
        $accountid  = $this->session->userdata('wow_sess_id');
        $item       = $this->getItem($id);
        if (!$item) return 'item_not_found';

        $realm      = $this->getCategoryRealmId($item['category']);
        $info       = $this->wowrealm->getRealm($realm)->row_array();
        $targetName = trim($targetName);
        $charRow    = $this->_charDb()
                           ->select('guid, name')
                           ->where('LOWER(name) =', strtolower($targetName))
                           ->get('characters')->row();
        if (!$charRow) return 'char_not_found';

        $targetGuid = (int)$charRow->guid;
        $targetName = $charRow->name;
        $dbDp       = (int)$item['dp'];
        $dbVp       = (int)$item['vp'];
        $cartDp     = ($cartDp !== null) ? (int)$cartDp : null;
        $cartVp     = ($cartVp !== null) ? (int)$cartVp : null;

        if ($pricechoosed == 1)      { $dpprice = ($cartDp !== null) ? $cartDp : $dbDp; $vpprice = 0; }
        elseif ($pricechoosed == 2)  { $dpprice = 0; $vpprice = ($cartVp !== null) ? $cartVp : $dbVp; }
        else {
            if ($item['price_type'] == 1)     { $dpprice = ($cartDp !== null) ? $cartDp : $dbDp; $vpprice = 0; }
            elseif ($item['price_type'] == 2) { $dpprice = 0; $vpprice = ($cartVp !== null) ? $cartVp : $dbVp; }
            else                              { $dpprice = ($cartDp !== null) ? $cartDp : $dbDp; $vpprice = ($cartVp !== null) ? $cartVp : $dbVp; }
        }

        if ($dpprice > 0 && $this->wowgeneral->getCharDPTotal($accountid) < $dpprice) return 'insufficient_dp';
        if ($vpprice > 0 && $this->wowgeneral->getCharVPTotal($accountid) < $vpprice) return 'insufficient_vp';

        $senderName = $this->db->select('username')->where('id', $accountid)->get('users')->row('username');
        $subject    = $this->lang->line('soap_gift_subject') ?: 'Vous avez reçu un cadeau !';
        $message    = $senderName . ' ' . ($this->lang->line('soap_gift_body') ?: 'vous a offert cet article via la boutique.');

        $this->_sendSoapCommand($item, $targetName, $subject, $message, $info);

        if ($dpprice > 0 && $vpprice > 0)
            $this->db->query("UPDATE users SET dp = (dp-$dpprice), vp = (vp-$vpprice) WHERE id = $accountid");
        elseif ($dpprice > 0)
            $this->db->query("UPDATE users SET dp = (dp-$dpprice) WHERE id = $accountid");
        elseif ($vpprice > 0)
            $this->db->query("UPDATE users SET vp = (vp-$vpprice) WHERE id = $accountid");

        $this->insertStoreLog($accountid, $targetGuid, '[GIFT→' . $targetName . '] ' . $item['name'],
                              $item['type'], $item['price_type'], $dpprice, $vpprice);
        return true;
    }

    public function checkGiftCharacter($charName)
    {
        $charName = trim($charName);
        if (strlen($charName) < 2 || strlen($charName) > 12)
            return ['exists' => false, 'guid' => null, 'name' => null];

        $row = $this->_charDb()
                    ->select('guid, name')
                    ->where('LOWER(name) =', strtolower($charName))
                    ->get('characters')->row();

        return $row
            ? ['exists' => true,  'guid' => (int)$row->guid, 'name' => $row->name]
            : ['exists' => false, 'guid' => null,             'name' => null];
    }

    public function Checkout($coupon = null)
    {
        $accountid = $this->session->userdata('wow_sess_id');
        $dptotal   = $this->cart->total_dp();
        $vptotal   = $this->cart->total_vp();

        // Appliquer la réduction coupon si présente
        if ($coupon && !empty($coupon['coupon_id'])) {
            $dptotal = max(0, $dptotal - (int)$coupon['discountDp']);
            $vptotal = max(0, $vptotal - (int)$coupon['discountVp']);
        }

        if ($this->wowgeneral->getCharDPTotal($accountid) >= $dptotal &&
            $this->wowgeneral->getCharVPTotal($accountid) >= $vptotal)
        {
            // Déduire la réduction globalement (1 seule déduction pour le coupon)
            if ($coupon && !empty($coupon['coupon_id'])) {
                $discDp = (int)$coupon['discountDp'];
                $discVp = (int)$coupon['discountVp'];
                if ($discDp > 0 && $discVp > 0) {
                    $this->db->query("UPDATE users SET dp = (dp+$discDp), vp = (vp+$discVp) WHERE id = $accountid");
                } elseif ($discDp > 0) {
                    $this->db->query("UPDATE users SET dp = (dp+$discDp) WHERE id = $accountid");
                } elseif ($discVp > 0) {
                    $this->db->query("UPDATE users SET vp = (vp+$discVp) WHERE id = $accountid");
                }
            }

            foreach ($this->cart->contents() as $item) {
                $pricechoosed = !empty($item['pricechoosed']) ? $item['pricechoosed'] : 0;
                $cartDp       = isset($item['dp']) ? (int)$item['dp'] : null;
                $cartVp       = isset($item['vp']) ? (int)$item['vp'] : null;
                for ($i = 0; $i < (int)$item['qty']; $i++) {
                    $this->PurchaseItem($item['id'], $item['guid'], $pricechoosed, $cartDp, $cartVp);
                }
            }
            $this->cart->destroy();
            return true;
        }
        return 'insPoints';
    }

    // ── SOAP ─────────────────────────────────────────────────

    private function _sendSoapCommand($item, $charname, $subject, $message, $info)
    {
        $cmd = $item['command'];
        $u   = $info['console_username'];
        $p   = $info['console_password'];
        $h   = $info['console_hostname'];
        $po  = $info['console_port'];
        $em  = $info['emulator'];

        switch ((int)$item['type']) {
            case 1: $this->wowrealm->commandSoap('.send items '         . $charname . ' "' . $subject . '" "' . $message . '" ' . $cmd, $u, $p, $h, $po, $em); break;
            case 2: $this->wowrealm->commandSoap('.send money '         . $charname . ' "' . $subject . '" "' . $message . '" ' . $cmd, $u, $p, $h, $po, $em); break;
            case 3: $this->wowrealm->commandSoap('.character level '    . $charname . ' '  . $cmd,    $u, $p, $h, $po, $em); break;
            case 4: $this->wowrealm->commandSoap('.character rename '   . $charname . ' ',             $u, $p, $h, $po, $em); break;
            case 5: $this->wowrealm->commandSoap('.character customize '. $charname . ' ',             $u, $p, $h, $po, $em); break;
            case 6: $this->wowrealm->commandSoap('.character changefaction ' . $charname . ' ',        $u, $p, $h, $po, $em); break;
            case 7: $this->wowrealm->commandSoap('.character changerace '    . $charname . ' ',        $u, $p, $h, $po, $em); break;
        }
    }

    // ── Promo / Bestseller ────────────────────────────────────

    private function _getPromoTtl(): int
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `store_promo_settings` (
            `id`             TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `promo_ttl_days` TINYINT UNSIGNED NOT NULL DEFAULT 7,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if ($this->db->count_all('store_promo_settings') == 0)
            $this->db->insert('store_promo_settings', ['id' => 1, 'promo_ttl_days' => 7]);
        $row = $this->db->select('promo_ttl_days')->get('store_promo_settings')->row();
        $days = ($row && (int)$row->promo_ttl_days > 0) ? (int)$row->promo_ttl_days : 7;
        return $days * 86400;
    }

    public function refreshWeeklyPromo()
    {
        $cacheTTL = $this->_getPromoTtl();
        $shortTTL = 30 * 24 * 3600;
        $minSales = 5;
        $now      = time();

        $bsData    = $this->_readSetting('store_bestseller');
        $promoData = $this->_readSetting('store_promo');

        $currentPromoName = null;
        $currentPromoId   = null;
        if ($promoData !== null && !empty($promoData['promo_item_id']) &&
            isset($promoData['promo_expires']) && $now < (int)$promoData['promo_expires']) {
            $currentPromoId   = (int)$promoData['promo_item_id'];
            $currentPromoName = $promoData['promo_item_name'] ?? null;
        }

        // ── BESTSELLER ──
        $bestItemId = null;
        if ($bsData !== null && isset($bsData['expires']) && $now < (int)$bsData['expires']) {
            $bestItemId = isset($bsData['item_id']) ? (int)$bsData['item_id'] : null;
        } else {
            $since = $now - $shortTTL;
            $q     = $this->db->select('item_name, COUNT(*) as total_sales')
                              ->where('date >=', $since)->where('price_type', 1)
                              ->where('item_name NOT LIKE', '[GIFT%');
            if ($currentPromoName !== null) $q = $q->where('item_name !=', $currentPromoName);
            $row = $q->group_by('item_name')->order_by('total_sales', 'DESC')->limit(1)->get('store_logs')->row();

            if (!$row || (int)$row->total_sales < $minSales) {
                $q2 = $this->db->select('item_name, COUNT(*) as total_sales')
                               ->where('price_type', 1)->where('item_name NOT LIKE', '[GIFT%');
                if ($currentPromoName !== null) $q2 = $q2->where('item_name !=', $currentPromoName);
                $row = $q2->group_by('item_name')->order_by('total_sales', 'DESC')->limit(1)->get('store_logs')->row();
            }

            $newBestId = null;
            if ($row && (int)$row->total_sales > 0) {
                $itemRow = $this->db->select('id')->where('name', $row->item_name)->where('price_type', 1)->get($this->item)->row();
                if ($itemRow) $newBestId = (int)$itemRow->id;
            }

            if ($newBestId === null || $newBestId === $currentPromoId) {
                $topRows = $this->db->select('st.store_item')
                                    ->from($this->top . ' st')
                                    ->join($this->item . ' si', 'si.id = st.store_item')
                                    ->where('si.price_type', 1)->order_by('st.id', 'ASC')
                                    ->get()->result();
                foreach ($topRows as $tr) {
                    if ((int)$tr->store_item !== $currentPromoId) { $newBestId = (int)$tr->store_item; break; }
                }
            }

            if ($newBestId !== null) {
                if ($bsData !== null && !empty($bsData['injected_top_id']))
                    $this->db->where('id', (int)$bsData['injected_top_id'])->delete($this->top);
                $injectedId = null;
                if ($this->db->where('store_item', $newBestId)->count_all_results($this->top) == 0) {
                    $this->db->insert($this->top, ['store_item' => $newBestId]);
                    $injectedId = $this->db->insert_id();
                }
                $bsData = ['item_id' => $newBestId, 'injected_top_id' => $injectedId,
                           'expires' => $now + $cacheTTL, 'computed' => date('Y-m-d H:i:s')];
                $this->_writeSetting('store_bestseller', $bsData);
                $bestItemId = $newBestId;
            }
            if ($bestItemId === null)
                $this->_writeSetting('store_bestseller', ['item_id' => null, 'expires' => $now + $cacheTTL]);
        }

        $bestItemName = ($bestItemId !== null) ? $this->getName($bestItemId) : null;

        // ── PROMO (pool pondéré) ──
        $promoItemId   = null;
        $promoDiscount = 0;
        if ($promoData !== null && isset($promoData['promo_expires']) && $now < (int)$promoData['promo_expires']) {
            $promoItemId   = !empty($promoData['promo_item_id'])  ? (int)$promoData['promo_item_id']  : null;
            $promoDiscount = !empty($promoData['promo_discount']) ? (int)$promoData['promo_discount'] : 0;
        } else {
            // Nettoyage de l'éventuel item injecté dans store_top par l'ancien algorithme
            if ($promoData !== null && !empty($promoData['promo_injected_top_id']))
                $this->db->where('id', (int)$promoData['promo_injected_top_id'])->delete($this->top);

            // Tirage 1 — sélection item pondérée dans le pool promo (store_items.promo = 1)
            $promoItems = $this->db->select('id, name, rate')
                                   ->where('promo', 1)
                                   ->where('rate >', 0)
                                   ->get($this->item)->result_array();

            $newPromoId   = null;
            $newPromoName = null;
            if (!empty($promoItems)) {
                $totalWeight = array_sum(array_column($promoItems, 'rate'));
                $rand        = mt_rand(1, max(1, $totalWeight));
                $cursor      = 0;
                foreach ($promoItems as $pi) {
                    $cursor += (int)$pi['rate'];
                    if ($rand <= $cursor) {
                        $newPromoId   = (int)$pi['id'];
                        $newPromoName = $pi['name'];
                        break;
                    }
                }
            }

            // Tirage 2 — sélection discount pondérée : 10/20/30/40/50%
            if ($newPromoId !== null) {
                $discountPool  = [10 => 60, 20 => 25, 30 => 10, 40 => 4, 50 => 1];
                $totalDWeight  = array_sum($discountPool);
                $dRand         = mt_rand(1, $totalDWeight);
                $dCursor       = 0;
                $promoDiscount = 10;
                foreach ($discountPool as $pct => $weight) {
                    $dCursor += $weight;
                    if ($dRand <= $dCursor) { $promoDiscount = $pct; break; }
                }

                // Injection dans store_top si pas déjà présent
                $injectedId = null;
                if ($this->db->where('store_item', $newPromoId)->count_all_results($this->top) == 0) {
                    $this->db->insert($this->top, ['store_item' => $newPromoId]);
                    $injectedId = $this->db->insert_id();
                }

                $promoData = [
                    'promo_item_id'         => $newPromoId,
                    'promo_item_name'       => $newPromoName,
                    'promo_discount'        => $promoDiscount,
                    'promo_injected_top_id' => $injectedId,
                    'promo_expires'         => $now + $cacheTTL,
                    'computed'              => date('Y-m-d H:i:s'),
                ];
                $this->_writeSetting('store_promo', $promoData);
                $promoItemId = $newPromoId;
            }
            if ($promoItemId === null)
                $this->_writeSetting('store_promo', ['promo_item_id' => null, 'promo_expires' => $now + $cacheTTL]);
        }

        return [
            'bestseller_id'  => $bestItemId,
            'promo_item_id'  => $promoItemId,
            'promo_discount' => $promoDiscount,
        ];
    }

    public function getPromoItem()
    {
        $data = $this->_readSetting('store_promo');
        if ($data === null || empty($data['promo_item_id'])) return null;
        if (!isset($data['promo_expires']) || time() >= (int)$data['promo_expires']) return null;
        return [
            'item_id'    => (int)$data['promo_item_id'],
            'item_name'  => (string)($data['promo_item_name'] ?? ''),
            'discount'   => (int)$data['promo_discount'],
            'expires_at' => (int)$data['promo_expires'],
        ];
    }

    public function getBestSellerWeek()
    {
        $data = $this->_readSetting('store_bestseller');
        if ($data === null || !isset($data['expires']) || time() >= (int)$data['expires']) return null;
        return isset($data['item_id']) ? (int)$data['item_id'] : null;
    }

    public function getPromoDiscount($itemId)
    {
        $data = $this->_readSetting('store_promo');
        if ($data === null || empty($data['promo_item_id']) || (int)$data['promo_item_id'] !== (int)$itemId) return 0;
        if (!isset($data['promo_expires']) || time() >= (int)$data['promo_expires']) return 0;
        return (int)$data['promo_discount'];
    }

    // ── Cache fichier ─────────────────────────────────────────

    private static $_settingsCache = [];

    private function _getCacheFile($key)
    {
        $root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : __FILE__;
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR
             . 'wowstore_' . substr(md5($root), 0, 8) . '_' . $key . '.json';
    }

    private function _readSetting($key)
    {
        if (isset(self::$_settingsCache[$key])) return self::$_settingsCache[$key];
        $file = $this->_getCacheFile($key);
        if (!file_exists($file)) return null;
        $raw  = @file_get_contents($file);
        if ($raw === false || $raw === '') return null;
        $data = json_decode($raw, true);
        if (!is_array($data)) return null;
        self::$_settingsCache[$key] = $data;
        return $data;
    }

    private function _writeSetting($key, array $data)
    {
        self::$_settingsCache[$key] = $data;
        @file_put_contents($this->_getCacheFile($key), json_encode($data));
    }
}
