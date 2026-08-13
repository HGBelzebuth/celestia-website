<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$CI =& get_instance();
$CI->load->model('donate/donate_model');
$CI->load->model('vote/vote_model');
$userId = $CI->session->userdata('wow_sess_id');
$curDp  = (int) $CI->donate_model->getCurrentDP();
$curVp  = (int) $CI->vote_model->getCredits($userId);

$cartItems     = [];
$giftItemsJson = [];

foreach ($this->cart->contents() as $item) {
    $itemData  = $this->store_model->getItem($item['id']);
    
    $pType = is_object($itemData) ? ($itemData->price_type ?? 0) : ($itemData['price_type'] ?? 0);
    $catId = is_object($itemData) ? ($itemData->category ?? 0)   : ($itemData['category'] ?? 0);
    $icn   = is_object($itemData) ? ($itemData->icon ?? '')      : ($itemData['icon'] ?? '');

    $priceType = (int)$pType;
    $isVpOnly  = ($priceType === 2);
    $isGift    = !empty($item['gift_char_name']);
    $giftName  = $isGift ? $item['gift_char_name'] : '';
    $realmId   = $this->store_model->getCategoryRealmId($catId);
    $itemIcon  = $template['assets'] . 'images/store/' . $icn . '.jpg';
    $unitDp    = (int)($item['dp'] ?? 0);
    $unitVp    = (int)($item['vp'] ?? 0);

    $cartItems[] = [
        'item'      => $item,
        'priceType' => $priceType,
        'isGift'    => $isGift,
        'giftName'  => $giftName,
        'realmId'   => $realmId,
        'itemIcon'  => $itemIcon,
        'unitDp'    => $unitDp,
        'unitVp'    => $unitVp,
    ];

    if (!$isGift && !$isVpOnly) {
        $giftItemsJson[] = [
            'rowid'    => $item['rowid'],
            'id'       => $item['id'],
            'name'     => $item['name'],
            'iconUrl'  => $itemIcon,
            'dp'       => $unitDp,
            'maxQty'   => (int)$item['qty'],
            'category' => $realmId,
        ];
    }
}
?>

<link rel="stylesheet" href="<?= $template['location'] ?>assets/css/cart.css?v=final">

<div class="cart-page-bg"></div>

<div class="cart-hero">
    <div class="cart-hero-bg" style="background-image:url('<?= $template['assets'].'core/css/images/entree_icc.png' ?>')"></div>
    <div class="cart-hero-overlay"></div>
    <div class="cart-hero-content cw-wrap">
        <div class="cart-hero-medallion"><i class="fas fa-shopping-cart"></i></div>
        <h1><span>Panier</span></h1>
        <p>Récapitulatif de votre commande</p>
    </div>
</div>

<div class="cart-layout">
    <div class="cart-container cw-wrap">
        <div class="cart-panel bento-card">

            <div class="cart-panel-header">
                <div class="cart-panel-header-icon"><i class="fas fa-shopping-basket"></i></div>
                <h5><?= $this->lang->line('tab_cart') ?></h5>
                <?php if ($this->cart->total_items() > 0): ?>
                <span class="cart-panel-header-badge" id="cartBadge">
                    <?= $this->cart->total_items() ?> article<?= $this->cart->total_items() > 1 ? 's' : '' ?>
                </span>
                <?php endif ?>
            </div>

            <?php if (!empty($cartItems)): ?>
            <div class="cart-table-wrap">
                <table class="cart-table">
                    <colgroup>
                        <col style="width:38%">
                        <col style="width:26%">
                        <col style="width:16%">
                        <col style="width:13%">
                        <col style="width:7%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><div class="cart-th-inner"><i class="fas fa-box-open"></i><?= $this->lang->line('table_header_item_name') ?></div></th>
                            <th><div class="cart-th-inner"><i class="fas fa-user"></i><?= $this->lang->line('table_header_character') ?></div></th>
                            <th><div class="cart-th-inner"><i class="fas fa-coins"></i><?= $this->lang->line('table_header_price') ?></div></th>
                            <th><div class="cart-th-inner"><i class="fas fa-layer-group"></i><?= $this->lang->line('table_header_quantity') ?></div></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $row):
                            $item      = $row['item'];
                            $priceType = $row['priceType'];
                            $isGift    = $row['isGift'];
                            $giftName  = $row['giftName'];
                            $realmId   = $row['realmId'];
                            $itemIcon  = $row['itemIcon'];
                            $unitDp    = $row['unitDp'];
                            $unitVp    = $row['unitVp'];
                        ?>
                        <tr id="row_<?= $item['rowid'] ?>" data-unit-dp="<?= $unitDp ?>" data-unit-vp="<?= $unitVp ?>">
                            
                            <td style="position:relative;overflow:hidden;">
                                <div class="cart-item-content">
                                    <div class="cart-item-cell">
                                        <div class="cart-item-icon" style="background-image:url('<?= $itemIcon ?>')"></div>
                                        <div>
                                            <div class="cart-item-name"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></div>
                                            <?php if ($isGift): ?>
                                            <div style="margin-top:6px;">
                                                <span class="cart-gift-badge"><i class="fas fa-gift"></i> Cadeau pour <?= htmlspecialchars($giftName, ENT_QUOTES) ?></span>
                                            </div>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="cart-td-char" data-label="Personnage">
                                <?php if ($isGift): ?>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="gift-pulse-dot"></div>
                                    <span style="color:var(--store-ice);font-size:1rem;font-weight:600;letter-spacing:0.04em;"><?= htmlspecialchars($giftName, ENT_QUOTES) ?></span>
                                    <span class="uk-badge" style="font-size:0.9rem;padding:2px 7px;">Destinataire</span>
                                </div>
                                <?php else: ?>
                                <?php
                                $realmConn = $this->wowrealm->getRealmConnectionData($realmId);
                                $realmName = $this->wowrealm->getRealmName($realmId);
                                $charList  = $this->wowrealm->getGeneralCharactersSpecifyAcc($realmConn, $userId)->result();
                                $selectedChar = null;
                                foreach ($charList as $lc) { if ($lc->guid == $item['guid']) { $selectedChar = $lc; break; } }
                                ?>
                                <select class="cart-char-select" style="display:none" data-guid="<?= $item['guid'] ?>" data-rowid="<?= $item['rowid'] ?>" id="sel_<?= $item['rowid'] ?>" onchange="updateCharacter(this, '<?= $item['rowid'] ?>')">
                                    <option value="0">Sélectionner un personnage</option>
                                    <?php foreach ($charList as $listchar): ?>
                                    <option value="<?= $listchar->guid ?>" <?= ($listchar->guid == $item['guid']) ? 'selected' : '' ?>><?= $listchar->name ?> — <?= $realmName ?></option>
                                    <?php endforeach ?>
                                </select>

                                <div class="cart-select-wrap" id="wrap_<?= $item['rowid'] ?>">
                                    <div class="csd" id="csd_<?= $item['rowid'] ?>" data-rowid="<?= $item['rowid'] ?>">
                                        <div class="csd__trigger" id="csdTrigger_<?= $item['rowid'] ?>">
                                            <?php if ($selectedChar): ?>
                                            <img class="csd__icon csd__icon--race" src="<?= base_url('assets/images/races/'.$this->wowgeneral->getRaceIcon($selectedChar->race)) ?>" alt="">
                                            <img class="csd__icon csd__icon--class" src="<?= base_url('assets/images/class/'.$this->wowgeneral->getClassIcon($selectedChar->class)) ?>" alt="">
                                            <div class="csd__trigger-info">
                                                <span class="csd__name"><?= htmlspecialchars($selectedChar->name) ?></span>
                                                <span class="csd__sub"><?= $this->wowgeneral->getClassName($selectedChar->class) ?> · Nv.<?= $selectedChar->level ?> · <?= $realmName ?></span>
                                            </div>
                                            <?php else: ?>
                                            <span class="csd__placeholder">Sélectionner un personnage</span>
                                            <?php endif ?>
                                            <i class="fas fa-chevron-down csd__arrow"></i>
                                        </div>
                                        <div class="csd__list" id="csdList_<?= $item['rowid'] ?>">
                                            <?php foreach ($charList as $listchar): ?>
                                            <div class="csd__option <?= ($listchar->guid == $item['guid']) ? 'csd__option--selected' : '' ?>" data-guid="<?= $listchar->guid ?>" data-raceicon="<?= base_url('assets/images/races/'.$this->wowgeneral->getRaceIcon($listchar->race)) ?>" data-classicon="<?= base_url('assets/images/class/'.$this->wowgeneral->getClassIcon($listchar->class)) ?>" data-name="<?= htmlspecialchars($listchar->name) ?>" data-realm="<?= $realmName ?>" data-classname="<?= $this->wowgeneral->getClassName($listchar->class) ?>" data-level="<?= $listchar->level ?>">
                                                <img class="csd__icon csd__icon--race" src="<?= base_url('assets/images/races/'.$this->wowgeneral->getRaceIcon($listchar->race)) ?>" alt="">
                                                <img class="csd__icon csd__icon--class" src="<?= base_url('assets/images/class/'.$this->wowgeneral->getClassIcon($listchar->class)) ?>" alt="">
                                                <div class="csd__info">
                                                    <span class="csd__info-name"><?= htmlspecialchars($listchar->name) ?></span>
                                                    <span class="csd__info-sub"><?= $this->wowgeneral->getClassName($listchar->class) ?> · Nv.<?= $listchar->level ?> · <?= $realmName ?></span>
                                                </div>
                                                <?php if ($listchar->guid == $item['guid']): ?><i class="fas fa-check csd__check"></i><?php endif ?>
                                            </div>
                                            <?php endforeach ?>
                                        </div>
                                    </div>
                                    <div class="cart-char-confirm" id="confirm_<?= $item['rowid'] ?>" style="display:none; color:#00ff88; font-size:0.85rem; margin-top:5px;">
                                        <i class="fas fa-check-circle"></i> Sélectionné
                                    </div>
                                </div>
                                <?php endif ?>
                            </td>

                            <td data-label="Prix">
                                <?php if ($priceType === 1): ?>
                                    <span class="cart-price"><?= $item['dp'] ?> <span uk-tooltip="title: <?= $this->lang->line('panel_dp') ?>"><i class="dp-icon"></i></span></span>
                                <?php elseif ($priceType === 2): ?>
                                    <span class="cart-price"><?= $item['vp'] ?> <span uk-tooltip="title: <?= $this->lang->line('panel_vp') ?>"><i class="vp-icon"></i></span></span>
                                <?php elseif ($priceType === 3): ?>
                                    <span class="cart-price"><?= $item['dp'] ?> <span uk-tooltip="title: <?= $this->lang->line('panel_dp') ?>"><i class="dp-icon"></i></span></span>
                                    <span class="cart-price" style="margin-top:4px;"><?= $item['vp'] ?> <span uk-tooltip="title: <?= $this->lang->line('panel_vp') ?>"><i class="vp-icon"></i></span></span>
                                <?php elseif ($priceType === 4): ?>
                                    <div style="margin-bottom:8px;">
                                        <select class="cart-select" style="min-width:90px;" onchange="updatePrice(this,'<?= $item['id'] ?>','<?= $item['rowid'] ?>')">
                                            <option value="1">DP</option><option value="2">VP</option>
                                        </select>
                                    </div>
                                    <?php if ($item['dp'] > 0): ?><span class="cart-price"><?= $item['dp'] ?> <i class="dp-icon"></i></span>
                                    <?php else: ?><span class="cart-price"><?= $item['vp'] ?> <i class="vp-icon"></i></span><?php endif ?>
                                <?php endif ?>
                            </td>

                            <td data-label="Quantité">
                                <div class="pn-qty-controls">
                                    <button class="pn-qty-btn" onclick="decrementQuantity('<?= $item['rowid'] ?>', this)">−</button>
                                    <input class="pn-qty-input cart-qty" type="number" value="<?= $item['qty'] ?>" data-rowid="<?= $item['rowid'] ?>" readonly>
                                    <button class="pn-qty-btn" onclick="incrementQuantity('<?= $item['rowid'] ?>', this)">+</button>
                                </div>
                            </td>

                            <td>
                                <div class="cart-item-actions">
                                    <button class="cart-btn-delete" value="<?= $item['rowid'] ?>" id="button_delete<?= $item['rowid'] ?>" onclick="deleteItem(event, '<?= $item['rowid'] ?>')"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <?php
            $activeCoupon = $this->session->userdata('active_coupon');
            $dispDp = $activeCoupon ? $activeCoupon['finalDp'] : $this->cart->total_dp();
            $dispVp = $activeCoupon ? $activeCoupon['finalVp'] : $this->cart->total_vp();
            ?>

            <div class="cart-coupon-bar" id="couponBar">
                <?php if ($activeCoupon): ?>
                <div class="coupon-active-wrap" id="couponActive">
                    <div class="coupon-active-badge">
                        <i class="fas fa-check-circle" style="color:#50c878;"></i> 
                        Code <span class="coupon-active-code"><?= htmlspecialchars($activeCoupon['code']) ?></span> appliqué !
                    </div>
                    <div class="coupon-active-discount">
                        <?php if ($activeCoupon['type'] === 'percent'): ?>
                            Réduction de <?= (int)$activeCoupon['value'] ?>%
                        <?php else: ?>
                            Réduction de <?= (int)$activeCoupon['value'] ?> <?= strtoupper($activeCoupon['currency']) ?>
                        <?php endif ?>
                    </div>
                    <button class="coupon-active-remove" id="couponRemoveBtn" title="Retirer le coupon">
                        <i class="fas fa-times"></i> Retirer
                    </button>
                </div>
                <?php else: ?>
                <div class="coupon-form" id="couponForm">
                    <i class="fas fa-tag coupon-form__icon"></i>
                    <input type="text" class="coupon-form__input" id="couponInput" placeholder="Saisir un code promo" maxlength="32" autocomplete="off">
                    <button class="btn-epic coupon-form__btn" id="couponApplyBtn">Appliquer</button>
                    <div class="coupon-msg" id="couponMsg"></div>
                </div>
                <?php endif ?>
            </div>

            <div class="cart-panel-footer">
                <a href="<?= base_url('store') ?>" class="cart-btn-back"><i class="fas fa-arrow-left"></i> <?= $this->lang->line('button_buying') ?></a>

                <div style="flex:1;display:flex;justify-content:center;">
                    <div class="cart-total-wrap">
                        <div class="cart-total-label">Total</div>
                        <div class="cart-total-sep"></div>
                        <div class="cart-total-amount" id="cartTotalAmount">
                            <?php if ($this->cart->total_dp() > 0): ?>
                            <?php if ($activeCoupon && $activeCoupon['discountDp'] > 0): ?><span class="coupon-strikethrough" id="totalDpStrike"><?= $this->cart->total_dp() ?></span><span id="totalDpDisplay" class="coupon-final"><?= $dispDp ?></span><?php else: ?><span id="totalDpDisplay"><?= $dispDp ?></span><?php endif ?>
                            <span uk-tooltip="title: <?= $this->lang->line('panel_dp') ?>"><i class="dp-icon"></i></span>
                            <?php endif ?>

                            <?php if ($this->cart->total_vp() > 0): ?>
                            <?php if ($activeCoupon && $activeCoupon['discountVp'] > 0): ?><span class="coupon-strikethrough" id="totalVpStrike"><?= $this->cart->total_vp() ?></span><span id="totalVpDisplay" class="coupon-final"><?= $dispVp ?></span><?php else: ?><span id="totalVpDisplay"><?= $dispVp ?></span><?php endif ?>
                            <span uk-tooltip="title: <?= $this->lang->line('panel_vp') ?>"><i class="vp-icon"></i></span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>

                <div class="cart-actions-right">
                    <button class="cart-btn-gift-simple" id="giftFooterButton" onclick="openGiftMultipleModal()"><i class="fas fa-gift"></i> Offrir</button>
                    <button class="btn-epic cart-btn-checkout" value="1" id="button_checkout" data-total-dp="<?= $dispDp ?>" data-total-vp="<?= $dispVp ?>" data-current-dp="<?= $curDp ?>" data-current-vp="<?= $curVp ?>"><i class="fas fa-check-circle"></i> <?= $this->lang->line('button_checkout') ?></button>
                </div>
            </div>

            <?php else: ?>
            <div class="cart-empty">
                <div class="cart-empty-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Votre panier est vide</h3>
                <p>Découvrez nos nouveautés dans la boutique pour commencer.</p>
                <br><a href="<?= base_url('store') ?>" class="btn-epic" style="display:inline-flex;margin-top:10px;">Aller à la boutique</a>
            </div>
            <?php endif ?>

        </div>
    </div>
</div>

<?= $this->load->view('_cart_modals', [], TRUE) ?>

<script>
var CART_URLS = {
    updateQuantity  : "<?= base_url($lang.'/cart/updatequantity') ?>",
    updatePrice     : "<?= base_url($lang.'/cart/updateprice') ?>",
    updateCharacter : "<?= base_url($lang.'/cart/updatecharacter') ?>",
    delete          : "<?= base_url($lang.'/cart/delete') ?>",
    checkout        : "<?= base_url($lang.'/cart/checkout') ?>",
    checkGiftChar   : "<?= base_url($lang.'/store/check_gift_char') ?>",
    sendMultipleGifts: "<?= base_url($lang.'/store/send_multiple_gifts') ?>",
    applyCoupon     : "<?= base_url($lang.'/store/apply_coupon') ?>",
    removeCoupon    : "<?= base_url($lang.'/store/remove_coupon') ?>",
    store           : "<?= base_url('store') ?>"
};
var CART_GIFT_ITEMS = <?= json_encode($giftItemsJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= $template['assets'] ?>js/cart.js?v=final"></script>