<link rel="stylesheet" href="<?= $template['location'] ?>assets/css/store-category.css">

<svg style="display:none" xmlns="http://www.w3.org/2000/svg">
  <symbol id="pn-corner-svg" viewBox="0 0 16 16" fill="none">
    <path d="M1 15 L1 3 Q1 1 3 1 L15 1" stroke="#00e1ff" stroke-width="1.1" stroke-linecap="round"/>
    <path d="M1 15 L1 6 Q1 3.5 3.5 3.5 L15 3.5" stroke="#00e1ff" stroke-width="0.45" stroke-linecap="round" opacity="0.45"/>
    <circle cx="2.8" cy="2.8" r="1.1" fill="#00e1ff" opacity="0.65"/>
  </symbol>
</svg>

<div class="store-page-bg"></div>

<div class="pn-store-hero">
    <div class="pn-store-hero-bg" style="background-image: url('<?= $template['assets'] ?>core/css/images/entree_icc.png');"></div>
    <div class="pn-store-hero-overlay"></div>
    <div class="pn-store-hero-content cw-wrap">
        <div class="pn-store-hero-medallion"><i class="fas fa-tag"></i></div>
        <h1><span><?= $this->store_model->getCategoryName($route); ?></span></h1>
        <p>Boutique — Celestia</p>
    </div>
</div>

<div class="pn-store-layout">
    <div class="pn-store-container cw-wrap">

        <aside class="pn-store-sidebar">
            <div class="pn-sidebar-header">
                <div class="pn-sidebar-header-icon"><i class="far fa-compass"></i></div>
                <h5><?= $this->lang->line('store_categories'); ?></h5>
            </div>
            <ul class="pn-sidebar-nav uk-nav-parent-icon" uk-nav>
                <li>
                    <a href="<?= base_url('store'); ?>">
                        <i class="fas fa-star"></i>
                        <?= $this->lang->line('store_top_items'); ?>
                    </a>
                </li>
                <?php foreach ($this->wowrealm->getRealms()->result() as $MultiRealm): ?>
                <div class="pn-sidebar-realm-divider"></div>
                <?php
                    $realmHasActive = false;
                    foreach($this->store_model->getCategories($MultiRealm->realmID)->result() as $cat) {
                        if (isset($route) && $cat->route === $route) { $realmHasActive = true; break; }
                        if ($cat->main == '2') {
                            foreach ($this->store_model->getChildStoreCategory($cat->id)->result() as $child) {
                                if (isset($route) && $child->route === $route) { $realmHasActive = true; break 2; }
                            }
                        }
                    }
                ?>
                <li class="uk-parent <?= $realmHasActive ? 'uk-open' : '' ?>">
                    <a href="javascript:void(0);">
                        <i class="fas fa-server"></i>
                        <?= $this->wowrealm->getRealmName($MultiRealm->realmID); ?>
                    </a>
                    <ul class="uk-nav-sub uk-nav-parent-icon" uk-nav>
                        <?php foreach($this->store_model->getCategories($MultiRealm->realmID)->result() as $menulist): ?>
                        <?php if($menulist->main == '2' && $menulist->father == '0'): ?>
                        <?php
                            $parentActive = false;
                            foreach ($this->store_model->getChildStoreCategory($menulist->id)->result() as $child) {
                                if (isset($route) && $child->route === $route) { $parentActive = true; break; }
                            }
                        ?>
                        <li class="uk-parent <?= $parentActive ? 'uk-open' : '' ?>">
                            <a href="#"><?= $menulist->name ?></a>
                            <ul class="uk-nav-sub">
                                <?php foreach ($this->store_model->getChildStoreCategory($menulist->id)->result() as $menuchildlist): ?>
                                <?php $isActive = isset($route) && $menuchildlist->route === $route; ?>
                                <li class="<?= $isActive ? 'pn-active' : '' ?>">
                                    <a href="<?= base_url('store/'.$menuchildlist->route) ?>"
                                       class="<?= $isActive ? 'pn-active' : '' ?>">
                                        <?= $menuchildlist->name ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php elseif($menulist->main == '1' && $menulist->father == '0'): ?>
                        <?php $isActive = isset($route) && $menulist->route === $route; ?>
                        <li class="<?= $isActive ? 'pn-active' : '' ?>">
                            <a href="<?= base_url('store/'.$menulist->route) ?>"
                               class="<?= $isActive ? 'pn-active' : '' ?>">
                                <?= $menulist->name ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="pn-store-main">
            <div class="pn-store-panel">
                <div class="pn-store-panel-header">
                    <div class="pn-store-panel-header-icon"><i class="fas fa-tag"></i></div>
                    <h5><?= $this->store_model->getCategoryName($route); ?></h5>
                </div>
                <div class="pn-store-panel-body">
                    <?php
                    $grouped = [];
                    foreach($this->store_model->getCategoryItems($route) as $item) {
                        $grouped[$item->name][] = $item;
                    }
                    ?>
                    <div class="pn-items-grid">
                        <?php foreach($grouped as $itemName => $variants): ?>
                        <?php
                            $first        = $variants[0];
                            $icon         = $template['assets'].'images/store/'.$first->icon.'.jpg';
                            $desc         = $first->description ?? '';
                            $wowhead      = $first->wowhead ?? '';
                            $variantsData = [];
                            foreach($variants as $v) {
                                $variantsData[] = [
                                    'id'   => $v->id,
                                    'type' => $v->price_type,
                                    'dp'   => $v->dp,
                                    'vp'   => $v->vp,
                                ];
                            }
                            $variantsJson = htmlspecialchars(json_encode($variantsData), ENT_QUOTES);
                            $isStacked    = ($variantsData[0]['type'] == 3 || $variantsData[0]['type'] == 4) && count($variantsData) === 1;
                        ?>
                        <div class="pn-item-card bento-card" data-variants="<?= $variantsJson ?>">

                            <div class="pn-item-card-image-wrap">
                                <img class="pn-item-card-img"
                                     src="<?= $icon ?>"
                                     alt="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>"
                                     loading="lazy">
                                <button class="pn-item-zoom-btn"
                                        title="Agrandir l'image"
                                        onclick="openImgModal('<?= htmlspecialchars($icon, ENT_QUOTES) ?>', '<?= htmlspecialchars($itemName, ENT_QUOTES) ?>')">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>

                            <div class="pn-item-card-body">
                                <div class="pn-item-card-sep"></div>
                                <div class="pn-item-card-name"><?= htmlspecialchars($itemName, ENT_QUOTES) ?></div>
                            </div>

                            <div class="pn-item-card-drawer">
                                <div class="pn-item-card-drawer-inner">
                                    <?php if($desc): ?>
                                    <div class="pn-item-drawer-desc" data-raw="<?= htmlspecialchars($desc, ENT_QUOTES) ?>"></div>
                                    <?php endif; ?>
                                    <?php if($wowhead && $wowhead !== '0' && $wowhead !== ''): ?>
                                    <a href="<?= htmlspecialchars($wowhead, ENT_QUOTES) ?>" target="_blank" class="pn-item-drawer-wowhead">
                                        <i class="fas fa-external-link-square-alt"></i> Détails Wowhead
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pn-item-card-footer<?= $isStacked ? ' pn-item-card-footer--stacked' : '' ?>">
                                <div class="pn-item-card-prices">
                                    <?php
                                    $hasDp = false; $hasVp = false; $dpVal = 0; $vpVal = 0;
                                    foreach($variants as $v) {
                                        if($v->price_type == 1)                              { $hasDp = true; $dpVal = $v->dp; }
                                        elseif($v->price_type == 2)                          { $hasVp = true; $vpVal = $v->vp; }
                                        elseif($v->price_type == 3 || $v->price_type == 4)  { $hasDp = true; $dpVal = $v->dp; $hasVp = true; $vpVal = $v->vp; }
                                    }
                                    ?>
                                    <?php if($hasDp && $hasVp): ?>
                                        <div class="pn-prices-stack">
                                            <span class="blizzcms-item-price dp-price"><?= $dpVal ?> <i class="dp-icon"></i></span>
                                            <span class="blizzcms-item-price vp-price"><?= $vpVal ?> <i class="vp-icon"></i></span>
                                        </div>
                                        <span class="pn-price-or">OU</span>
                                    <?php elseif($hasDp): ?>
                                        <span class="blizzcms-item-price dp-price"><?= $dpVal ?> <i class="dp-icon"></i></span>
                                    <?php elseif($hasVp): ?>
                                        <span class="blizzcms-item-price vp-price"><?= $vpVal ?> <i class="vp-icon"></i></span>
                                    <?php endif; ?>
                                </div>

                                <?php if(count($variantsData) === 1): ?>
                                <button class="pn-item-card-footer-add pn-add-btn"
                                        data-id="<?= $variantsData[0]['id'] ?>"
                                        data-name="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>"
                                        data-icon="<?= htmlspecialchars($icon, ENT_QUOTES) ?>"
                                        data-dp="<?= $variantsData[0]['dp'] ?>"
                                        data-vp="<?= $variantsData[0]['vp'] ?>"
                                        data-type="<?= $variantsData[0]['type'] ?>">
                                    <i class="fas fa-shopping-bag"></i> Obtenir
                                </button>
                                <?php else: ?>
                                <button class="pn-item-card-footer-add pn-item-card-footer-add--picker"
                                        onclick="togglePicker(event, this.nextElementSibling)">
                                    <i class="fas fa-coins"></i> Choisir
                                </button>
                                <div class="pn-drawer-picker">
                                    <div class="pn-drawer-picker-title">Quelle monnaie ?</div>
                                    <?php foreach($variantsData as $v): ?>
                                    <?php if($v['type'] == 1): ?>
                                    <div class="pn-drawer-picker-option pn-add-btn"
                                         data-id="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>"
                                         data-icon="<?= htmlspecialchars($icon, ENT_QUOTES) ?>" data-dp="<?= $v['dp'] ?>" data-vp="0" data-type="1">
                                        Payer en DP <span class="opt-price"><?= $v['dp'] ?> <i class="dp-icon"></i></span>
                                    </div>
                                    <?php elseif($v['type'] == 2): ?>
                                    <div class="pn-drawer-picker-option pn-add-btn"
                                         data-id="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>"
                                         data-icon="<?= htmlspecialchars($icon, ENT_QUOTES) ?>" data-dp="0" data-vp="<?= $v['vp'] ?>" data-type="2">
                                        Payer en VP <span class="opt-price"><?= $v['vp'] ?> <i class="vp-icon"></i></span>
                                    </div>
                                    <?php elseif($v['type'] == 3 || $v['type'] == 4): ?>
                                    <div class="pn-drawer-picker-option pn-add-btn"
                                         data-id="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>"
                                         data-icon="<?= htmlspecialchars($icon, ENT_QUOTES) ?>" data-dp="<?= $v['dp'] ?>" data-vp="0" data-type="1">
                                        Payer en DP <span class="opt-price"><?= $v['dp'] ?> <i class="dp-icon"></i></span>
                                    </div>
                                    <div class="pn-drawer-picker-option pn-add-btn"
                                         data-id="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($itemName, ENT_QUOTES) ?>"
                                         data-icon="<?= htmlspecialchars($icon, ENT_QUOTES) ?>" data-dp="0" data-vp="<?= $v['vp'] ?>" data-type="2">
                                        Payer en VP <span class="opt-price"><?= $v['vp'] ?> <i class="vp-icon"></i></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <button class="pn-item-details-band"
                                    onclick="toggleDrawer(event, this.closest('.pn-item-card'))">
                                <i class="fas fa-list-ul"></i>
                                Plus d'infos
                                <span class="pn-details-chevron"><i class="fas fa-chevron-down"></i></span>
                            </button>

                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>

    </div>
</div>

<div class="pn-img-overlay" id="pnImgOverlay" onclick="pnImgOverlayClick(event)">
    <div class="pn-img-modal">
        <button class="pn-img-modal-close" onclick="closeImgModal()"><i class="fas fa-times"></i></button>
        <img class="pn-img-modal-img" id="pnImgModalSrc" src="" alt="">
        <div class="pn-img-modal-name" id="pnImgModalName"></div>
    </div>
</div>

<div class="pn-qty-overlay" id="pnQtyOverlay" onclick="pnOverlayClick(event)" style="display:none">
    <div class="pn-qty-modal">
        <button class="pn-qty-close" onclick="closeQtyModal()"><i class="fas fa-times"></i></button>
        <img class="pn-qty-modal-icon" id="pnQtyIcon" src="" alt="">
        <div class="pn-qty-modal-title" id="pnQtyTitle"></div>
        <div class="pn-qty-modal-sub">Sélectionner la quantité</div>
        <div class="pn-qty-total">
            <span class="pn-qty-total-label">Total :</span>
            <span class="pn-qty-total-value" id="pnQtyTotalValue">—</span>
        </div>
        <div class="pn-qty-modal-sep"></div>
        <span class="pn-qty-label">Quantité</span>
        
        <div class="pn-qty-controls">
            <button class="pn-qty-btn" onclick="changeQty(-1)">−</button>
            <input class="pn-qty-input" type="number" id="pnQtyValue" min="1" max="99" value="1">
            <button class="pn-qty-btn" onclick="changeQty(1)">+</button>
        </div>

        <div class="pn-qty-modal-actions">
            <button class="pn-qty-cancel" onclick="closeQtyModal()">Annuler</button>
            <button class="pn-qty-confirm" onclick="confirmAddItem()"><i class="fas fa-cart-plus"></i> Ajouter</button>
        </div>
    </div>
</div>

<div class="pn-cart-overlay" id="pnCartOverlay" onclick="pnCartOverlayClick(event)">
    <div class="pn-cart-modal">
        <button class="pn-cart-modal-close" onclick="closeCartConfirmModal()"><i class="fas fa-times"></i></button>
        <div class="pn-cart-modal-check"><i class="fas fa-check"></i></div>
        <img class="pn-cart-modal-img" id="pnCartConfirmIcon" src="" alt="" style="display:none">
        <div class="pn-cart-modal-title">Ajouté au panier !</div>
        <div class="pn-cart-modal-item" id="pnCartConfirmName"></div>
        <div class="pn-cart-modal-sub">Article ajouté avec succès</div>
        <div class="pn-cart-modal-sep"></div>
        <div class="pn-cart-modal-actions">
            <button class="pn-cart-back-btn" onclick="closeCartConfirmModal()">
                <i class="fas fa-store"></i> Continuer
            </button>
            <a class="pn-cart-go-btn" href="<?= base_url($lang.'/cart'); ?>">
                <i class="fas fa-shopping-cart"></i> Mon panier
            </a>
        </div>
    </div>
</div>

<script>
var STORE_URLS = { addToCart: "<?= base_url($lang.'/cart/add') ?>" };
</script>
<script src="<?= $template['assets'] ?>js/store-category.js"></script>