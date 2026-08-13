<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('store_model');

        if (!ini_get('date.timezone'))
            date_default_timezone_set($this->config->item('timezone'));
    }

    // ── Guards ───────────────────────────────────────────────

    private function _pageGuard()
    {
        if (!$this->wowgeneral->getMaintenance())
            redirect(base_url('maintenance'), 'refresh');
        if (!$this->wowmodule->getStoreStatus())
            redirect(base_url(), 'refresh');
        if (!$this->wowauth->isLogged())
            redirect(base_url('login'), 'refresh');
    }

    private function _jsonAuthCheck()
    {
        if (!$this->wowgeneral->getMaintenance()) {
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'maintenance']); return false;
        }
        if (!$this->wowmodule->getStoreStatus()) {
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'store_disabled']); return false;
        }
        if (!$this->wowauth->isLogged()) {
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'not_logged']); return false;
        }
        return true;
    }

    private function _getRealIp()
    {
        $cfIp = $this->input->server('HTTP_CF_CONNECTING_IP');
        return ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) ? $cfIp : $this->input->ip_address();
    }

    private function _isRateLimited($key, $maxPerWindow, $windowSeconds)
    {
        $fullKey = $key . '_' . md5($this->_getRealIp());
        $now     = time();
        $spam    = $this->session->userdata($fullKey);

        if (!$spam || ($now - (int)$spam['start']) > $windowSeconds) {
            $this->session->set_userdata($fullKey, ['start' => $now, 'count' => 1]);
            return FALSE;
        }

        $spam['count']++;
        $this->session->set_userdata($fullKey, $spam);
        return ((int)$spam['count'] > $maxPerWindow);
    }

    // ── Pages ─────────────────────────────────────────────────

    public function index()
    {
        $this->_pageGuard();
        $weekly = $this->store_model->refreshWeeklyPromo();

        $data = [
            'pagetitle'      => $this->lang->line('tab_store'),
            'lang'           => $this->lang->lang(),
            'bestseller_id'  => $weekly['bestseller_id'],
            'promo_item_id'  => $weekly['promo_item_id'],
            'promo_discount' => $weekly['promo_discount'],
        ];

        $this->template->build('index', $data);
    }

    public function category($route)
    {
        $this->_pageGuard();
        if (empty($route) || $this->store_model->getCategoryExist($route) < 1)
            redirect(base_url('store'), 'refresh');

        $data = [
            'route'     => $route,
            'pagetitle' => $this->lang->line('tab_store') . ' | ' . $this->store_model->getCategoryName($route),
            'lang'      => $this->lang->lang(),
        ];

        $this->template->build('category', $data);
    }

    public function cart()
    {
        $this->_pageGuard();
        $this->template->build('cart', [
            'pagetitle' => $this->lang->line('tab_cart'),
            'lang'      => $this->lang->lang(),
        ]);
    }

    // ── Actions panier ────────────────────────────────────────

    public function addtocart()
    {
        $id      = $this->input->post('value');
        $dpPost  = $this->input->post('dp');
        $vpPost  = $this->input->post('vp');
        $dpPrice = ($dpPost !== false && $dpPost !== null && $dpPost !== '')
            ? max(0, (int)$dpPost)
            : (int)$this->store_model->getPriceDP($id);
        $vpPrice = ($vpPost !== false && $vpPost !== null && $vpPost !== '')
            ? max(0, (int)$vpPost)
            : (int)$this->store_model->getPriceVP($id);

        $item = $this->store_model->getItem($id);
        $name = $item ? stripslashes($item['name']) : 'Item #'.$id;

        $data = [
            'id'       => (int)$id,
            'name'     => $name,
            'price'    => max(1, $dpPrice ?: $vpPrice),
            'qty'      => max(1, (int)$this->input->post('qty')),
            'category' => $this->store_model->getCategory($id),
            'guid'     => 0,
            'dp'       => $dpPrice,
            'vp'       => $vpPrice,
            'options'  => ['Key' => uniqid()],
        ];

        $this->cart->product_name_safe = FALSE;
        echo $this->cart->insert($data) ? 'true' : 'false';
    }

    /**
     * Suppression d'un article du panier.
     * Renommé "delete" pour correspondre à l'URL /cart/delete utilisée dans cart.js.
     * (L'ancienne méthode removeitem reste en alias pour la compatibilité.)
     */
    public function delete()
    {
        $rowid = $this->input->post('value');
        echo $this->cart->remove($rowid) ? 'true' : 'false';
    }

    /** Alias de compatibilité */
    public function removeitem()
    {
        return $this->delete();
    }

    public function updatequantity()
    {
        $rowid = $this->input->get('rowid');
        $qty   = $this->input->get('qty');

        if (!empty($rowid) && !empty($qty) && $qty > 0) {
            echo $this->cart->update(['rowid' => $rowid, 'qty' => $qty]) ? 'true' : 'false';
        } else {
            echo 'false';
        }
    }

    public function updateprice()
    {
        $priceType = $this->input->get('pricechoosed');
        $rowid     = $this->input->get('rowid');
        $itemId    = $this->input->get('id');

        $data = ($priceType == 1)
            ? ['rowid' => $rowid, 'dp' => $this->store_model->getPriceDP($itemId), 'vp' => 0]
            : ['rowid' => $rowid, 'vp' => $this->store_model->getPriceVP($itemId), 'dp' => 0];

        echo $this->cart->update($data) ? 'true' : 'false';
    }

    public function updatecharacter()
    {
        $rowid = $this->input->get('rowid');
        $guid  = $this->input->get('char');

        if (!empty($rowid) && !empty($guid)) {
            echo $this->cart->update(['rowid' => $rowid, 'guid' => $guid]) ? 'true' : 'false';
        } else {
            echo 'false';
        }
    }

    public function checkout()
    {
        if ($this->cart->count_items() != $this->cart->valid_items()) {
            echo 'Selchars'; return;
        }

        // Récupérer le coupon actif en session
        $couponData = $this->session->userdata('active_coupon');
        $result = $this->store_model->Checkout($couponData ?: null);

        if ($result === true) {
            $userid = (int)$this->session->userdata('wow_sess_id');
            // Marquer le coupon utilisé
            if ($couponData && !empty($couponData['coupon_id'])) {
                $this->load->model('store/store_coupon_model');
                $this->store_coupon_model->markUsed((int)$couponData['coupon_id'], $userid);
            }
            $this->session->unset_userdata('active_coupon');
            $this->load->model('store/store_cart_model');
            $this->store_cart_model->clearSavedCart($userid);
        }
        echo $result === true ? 'true' : $result;
    }

    // ── Coupon ────────────────────────────────────────────────

    public function apply_coupon()
    {
        ob_clean();
        header('Content-Type: application/json');
        if (!$this->_jsonAuthCheck()) return;

        $code    = strtoupper(trim($this->input->post('code')));
        $userid  = (int)$this->session->userdata('wow_sess_id');
        $totalDp = (int)$this->cart->total_dp();
        $totalVp = (int)$this->cart->total_vp();

        if ($this->_isRateLimited('coupon_apply', 5, 30)) {
            echo json_encode(['success' => false, 'error' => 'rate_limited']); return;
        }

        $this->load->model('store/store_coupon_model');
        $result = $this->store_coupon_model->validate($code, $userid, $totalDp, $totalVp);

        if (!$result['valid']) {
            echo json_encode(['success' => false, 'error' => $result['error']]); return;
        }

        $coupon = $result['coupon'];

        $this->session->set_userdata('active_coupon', [
            'coupon_id'  => $coupon->id,
            'code'       => $coupon->code,
            'type'       => $coupon->type,
            'value'      => $coupon->value,
            'currency'   => $coupon->currency,
            'discountDp' => $result['discountDp'],
            'discountVp' => $result['discountVp'],
            'finalDp'    => $result['finalDp'],
            'finalVp'    => $result['finalVp'],
        ]);

        echo json_encode([
            'success'    => true,
            'code'       => $coupon->code,
            'type'       => $coupon->type,
            'value'      => $coupon->value,
            'currency'   => $coupon->currency,
            'discountDp' => $result['discountDp'],
            'discountVp' => $result['discountVp'],
            'finalDp'    => $result['finalDp'],
            'finalVp'    => $result['finalVp'],
        ]);
    }

    public function history()
    {
        $this->_pageGuard();
        $accountid = (int)$this->session->userdata('wow_sess_id');
        $page      = max(1, (int)$this->input->get('page'));
        $perPage   = 20;
        $offset    = ($page - 1) * $perPage;

        $total = $this->db
            ->where('accountid', $accountid)
            ->count_all_results('store_logs');

        $logs = $this->db
            ->where('accountid', $accountid)
            ->order_by('date', 'DESC')
            ->limit($perPage, $offset)
            ->get('store_logs')
            ->result();

        // Résoudre les noms de personnages via Realm_model
        $charNames = [];
        $realms    = $this->wowrealm->getRealms()->result();

        foreach ($logs as $log) {
            $guid = (int)$log->charid;
            if ($guid <= 0 || isset($charNames[$guid])) continue;

            foreach ($realms as $realm) {
                $conn = $this->wowrealm->getRealmConnectionData($realm->id);
                $name = $this->wowrealm->getNameCharacterSpecifyGuid($conn, $guid);
                if ($name) {
                    $charNames[$guid] = $name . ' — ' . $this->wowrealm->getRealmName($realm->id);
                    break;
                }
            }
        }

        $totalPages = max(1, ceil($total / $perPage));

        $this->template->build('history', [
            'logs'       => $logs,
            'charNames'  => $charNames,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }

        public function remove_coupon()
    {
        ob_clean();
        header('Content-Type: application/json');
        if (!$this->_jsonAuthCheck()) return;
        $this->session->unset_userdata('active_coupon');
        echo json_encode(['success' => true]);
    }

    // ── Cadeaux ───────────────────────────────────────────────

    public function check_gift_char()
    {
        ob_clean();
        header('Content-Type: application/json');
        if (!$this->_jsonAuthCheck()) return;

        $charName = trim($this->input->post('char_name'));
        if (strlen($charName) < 2 || strlen($charName) > 12) {
            echo json_encode(['status' => 'error', 'msg' => 'invalid_input']); return;
        }
        if ($this->_isRateLimited('gift_check', 8, 10)) {
            echo json_encode(['status' => 'error', 'msg' => 'rate_limited']); return;
        }

        $result = $this->store_model->checkGiftCharacter($charName);
        echo $result['exists']
            ? json_encode(['status' => 'ok',        'name' => $result['name'], 'guid' => $result['guid']])
            : json_encode(['status' => 'not_found', 'name' => $charName]);
    }

    public function send_gift()
    {
        $rowid    = $this->input->post('rowid');
        $charName = trim($this->input->post('char_name'));
        $charGuid = (int)$this->input->post('char_guid');

        if (empty($rowid) || empty($charName) || $charGuid < 1) { echo 'error'; return; }
        if ($this->_isRateLimited('gift_send', 3, 60)) { echo 'rate_limited'; return; }

        $cartItem = null;
        foreach ($this->cart->contents() as $ci) {
            if ($ci['rowid'] === $rowid) { $cartItem = $ci; break; }
        }
        if (!$cartItem) { echo 'error'; return; }

        $check = $this->store_model->checkGiftCharacter($charName);
        if (!$check['exists'] || (int)$check['guid'] !== $charGuid) { echo 'char_not_found'; return; }

        $itemId       = $cartItem['id'];
        $pricechoosed = !empty($cartItem['pricechoosed']) ? (int)$cartItem['pricechoosed'] : 0;
        $cartDp       = isset($cartItem['dp']) ? (int)$cartItem['dp'] : null;
        $cartVp       = isset($cartItem['vp']) ? (int)$cartItem['vp'] : null;

        $result = true;
        for ($i = 0; $i < (int)$cartItem['qty']; $i++) {
            $res = $this->store_model->GiftItem($itemId, $charName, $pricechoosed, $cartDp, $cartVp);
            if ($res !== true) { $result = $res; break; }
        }

        if ($result === true) {
            $this->cart->remove($rowid);
            if ($this->cart->total_items() === 0) {
                $this->load->model('store/store_cart_model');
                $this->store_cart_model->clearSavedCart((int)$this->session->userdata('wow_sess_id'));
            }
            echo 'ok';
        } elseif ($result === 'insufficient_dp' || $result === 'insufficient_vp') {
            echo 'insPoints';
        } elseif ($result === 'char_not_found') {
            echo 'char_not_found';
        } else {
            echo 'error';
        }
    }

    public function send_multiple_gifts()
    {
        ob_clean();
        header('Content-Type: application/json');
        if (!$this->_jsonAuthCheck()) return;

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']); return;
        }

        $charName = trim($input['char_name'] ?? '');
        $charGuid = (int)($input['char_guid'] ?? 0);
        $items    = $input['items'] ?? [];

        if (empty($charName) || $charGuid < 1 || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']); return;
        }
        if ($this->_isRateLimited('gift_send_multiple', 5, 60)) {
            echo json_encode(['success' => false, 'message' => 'Trop de requêtes, veuillez patienter']); return;
        }

        $check = $this->store_model->checkGiftCharacter($charName);
        if (!$check['exists'] || (int)$check['guid'] !== $charGuid) {
            echo json_encode(['success' => false, 'message' => 'Personnage introuvable']); return;
        }

        // Indexer le panier une fois (O(1) lookup dans la boucle)
        $cartIndex = [];
        foreach ($this->cart->contents() as $ci) {
            $cartIndex[$ci['rowid']] = $ci;
        }

        $successItems  = [];
        $failedItems   = [];
        $totalDpCost   = 0;
        $totalVpCost   = 0;
        $itemsToRemove = [];

        foreach ($items as $itemData) {
            $rowid    = $itemData['rowid'] ?? '';
            $qty      = (int)($itemData['qty'] ?? 1);
            $cartItem = $cartIndex[$rowid] ?? null;

            if (!$cartItem) { $failedItems[] = ['name' => 'Article inconnu', 'reason' => 'non_trouve']; continue; }
            if ($qty > (int)$cartItem['qty']) { $failedItems[] = ['name' => $cartItem['name'], 'reason' => 'quantite_insuffisante']; continue; }

            $itemId       = $cartItem['id'];
            $pricechoosed = !empty($cartItem['pricechoosed']) ? (int)$cartItem['pricechoosed'] : 0;
            $cartDp       = isset($cartItem['dp']) ? (int)$cartItem['dp'] : null;
            $cartVp       = isset($cartItem['vp']) ? (int)$cartItem['vp'] : null;

            $sentCount   = 0;
            $itemSuccess = true;
            for ($i = 0; $i < $qty; $i++) {
                $res = $this->store_model->GiftItem($itemId, $charName, $pricechoosed, $cartDp, $cartVp);
                if ($res === true) { $sentCount++; continue; }
                $itemSuccess = false;
                $reason = ($res === 'insufficient_dp' || $res === 'insufficient_vp') ? 'points_insuffisants' : 'erreur_envoi';
                $failedItems[] = ['name' => $cartItem['name'], 'reason' => $reason];
                break;
            }

            if ($itemSuccess && $sentCount > 0) {
                if ($sentCount == (int)$cartItem['qty']) {
                    $itemsToRemove[] = $rowid;
                } else {
                    $this->cart->update(['rowid' => $rowid, 'qty' => $cartItem['qty'] - $sentCount]);
                }
                $successItems[] = ['name' => $cartItem['name'], 'qty' => $sentCount];
                if ($cartDp > 0) $totalDpCost += $cartDp * $sentCount;
                if ($cartVp > 0) $totalVpCost += $cartVp * $sentCount;
            }
        }

        foreach ($itemsToRemove as $rowid) {
            $this->cart->remove($rowid);
        }

        if ($this->cart->total_items() === 0) {
            $this->load->model('store/store_cart_model');
            $this->store_cart_model->clearSavedCart((int)$this->session->userdata('wow_sess_id'));
        }

        if (!empty($successItems)) {
            echo json_encode([
                'success' => true,
                'message' => 'Cadeaux envoyés avec succès',
                'data'    => ['success_items' => $successItems, 'failed_items' => $failedItems,
                              'total_dp' => $totalDpCost, 'total_vp' => $totalVpCost],
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Aucun cadeau n'a pu être envoyé",
                'data'    => ['failed_items' => $failedItems],
            ]);
        }
    }

    public function get_user_points()
    {
        ob_clean();
        header('Content-Type: application/json');
        if (!$this->_jsonAuthCheck()) return;

        $this->load->model('donate/donate_model');
        $this->load->model('vote/vote_model');

        $userId = $this->session->userdata('wow_sess_id');
        echo json_encode([
            'success' => true,
            'dp'      => (int)$this->donate_model->getCurrentDP(),
            'vp'      => (int)$this->vote_model->getCredits($userId),
        ]);
    }
}
