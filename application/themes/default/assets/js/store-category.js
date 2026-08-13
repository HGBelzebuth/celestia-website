/* ══════════════════════════════════════════════════════════
   STORE-CATEGORY.JS — Celestia-WoW — Page catégorie
   ══════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── État modal quantité ── */
    var _pendingItemId = null;
    var _baseDP        = 0;
    var _baseVP        = 0;
    var _priceType     = 0;
    var _lastItemName  = '';
    var _lastItemIcon  = '';

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       DÉLÉGATION DU CLIC — bouton Ajouter / option picker
    ════════════════════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.pn-add-btn');
        if (!btn) return;
        e.stopPropagation();
        e.preventDefault();
        openQtyModal(
            btn.getAttribute('data-id'),
            btn.getAttribute('data-name'),
            btn.getAttribute('data-icon'),
            btn.getAttribute('data-dp')   || 0,
            btn.getAttribute('data-vp')   || 0,
            btn.getAttribute('data-type') || 0
        );
    });

    /* Fermer le picker devise au clic extérieur */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.pn-item-card-footer-add--picker') && !e.target.closest('.pn-drawer-picker')) {
            document.querySelectorAll('.pn-drawer-picker').forEach(function (p) {
                p.style.display = 'none';
            });
        }
    });

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       MODAL IMAGE (LOUPE)
    ════════════════════════════════════════════════════════ */
    function openImgModal(src, name) {
        document.getElementById('pnImgModalSrc').src   = src;
        document.getElementById('pnImgModalName').textContent = name || '';
        var overlay = document.getElementById('pnImgOverlay');
        overlay.style.display = 'flex';
        overlay.offsetHeight; // reflow pour l'animation
        overlay.classList.add('open');
    }

    function closeImgModal() {
        var overlay = document.getElementById('pnImgOverlay');
        overlay.classList.remove('open');
        overlay.style.display = 'none';
    }

    function pnImgOverlayClick(e) { if (e.target === e.currentTarget) closeImgModal(); }

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       MODAL QUANTITÉ
    ════════════════════════════════════════════════════════ */
    function openQtyModal(itemId, itemName, iconSrc, dp, vp, ptype) {
        _pendingItemId = itemId;
        _baseDP        = parseFloat(dp)  || 0;
        _baseVP        = parseFloat(vp)  || 0;
        _priceType     = parseInt(ptype) || 0;
        _lastItemName  = itemName || '';
        _lastItemIcon  = iconSrc  || '';

        document.getElementById('pnQtyValue').value = 1;
        document.getElementById('pnQtyTitle').textContent = itemName || '';
        var iconEl = document.getElementById('pnQtyIcon');
        iconEl.src          = iconSrc || '';
        iconEl.style.display = iconSrc ? 'block' : 'none';
        _updateTotal(1);
        document.getElementById('pnQtyOverlay').style.display = 'flex';
    }

    function _updateTotal(qty) {
        var el = document.getElementById('pnQtyTotalValue');
        if (!el) return;
        var parts = [];
        if (_priceType == 1 || _priceType == 3 || _priceType == 4)
            parts.push('<i class="dp-icon"></i> ' + (_baseDP * qty));
        if (_priceType == 2 || _priceType == 3 || _priceType == 4) {
            if (parts.length) parts.push('<span style="color:rgba(168,184,200,0.32);font-size:1rem;margin:0 2px">&amp;</span>');
            parts.push('<i class="vp-icon"></i> ' + (_baseVP * qty));
        }
        el.innerHTML = parts.length ? parts.join('') : '—';
    }

    function _changeQty(delta) {
        var input = document.getElementById('pnQtyValue');
        var v = Math.max(1, Math.min(99, (parseInt(input.value) || 1) + delta));
        input.value = v;
        _updateTotal(v);
    }

    function closeQtyModal() {
        document.getElementById('pnQtyOverlay').style.display = 'none';
        _pendingItemId = null;
    }
    function pnOverlayClick(e) { if (e.target === e.currentTarget) closeQtyModal(); }

    function confirmAddItem() {
        if (!_pendingItemId) return;
        var qty    = Math.max(1, parseInt(document.getElementById('pnQtyValue').value) || 1);
        var itemId = _pendingItemId;
        var dp     = _baseDP;
        var vp     = _baseVP;
        closeQtyModal();
        _doAddItem(itemId, qty, dp, vp);
    }

    document.getElementById('pnQtyValue').addEventListener('input', function () {
        var v = Math.max(1, Math.min(99, parseInt(this.value) || 1));
        _updateTotal(v);
    });

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       DRAWER DESCRIPTION
    ════════════════════════════════════════════════════════ */
    function toggleDrawer(e, card) {
        e.stopPropagation();
        var grid   = card.closest('.pn-items-grid');
        var isOpen = card.classList.contains('expanded');
        (grid || document).querySelectorAll('.pn-item-card.expanded').forEach(function (c) {
            if (c !== card) c.classList.remove('expanded');
        });
        card.classList.toggle('expanded', !isOpen);
        if (!isOpen) _parseDrawer(card);
    }

    function _parseDrawer(card) {
        var desc = card.querySelector('.pn-item-drawer-desc');
        if (!desc || desc.dataset.parsed) return;
        var raw = desc.getAttribute('data-raw') || '';
        if (!raw) { desc.dataset.parsed = '1'; return; }
        var lines = raw.split(/\$B|\n|\r\n/)
            .map(function (l) { return l.trim(); })
            .filter(function (l) { return l.length > 0; });
        var html = '';
        if (lines.length <= 1) {
            var txt = (lines[0] || raw.trim())
                .replace(/(?<![a-zA-ZÀ-ÿ])(x\d+)(?![a-zA-ZÀ-ÿ])/gi, '<span class="pn-desc-qty">$1</span>');
            html = '<div class="pn-desc-simple">' + txt + '</div>';
        } else {
            html = '<ul class="pn-desc-list">';
            lines.forEach(function (line) {
                line = line
                    .replace(/^[-–—]\s*/, '')
                    .replace(/(?<![a-zA-ZÀ-ÿ])(x\d+)(?![a-zA-ZÀ-ÿ])/gi, '<span class="pn-desc-qty">$1</span>');
                html += '<li><span class="pn-desc-line-text">' + line + '</span></li>';
            });
            html += '</ul>';
        }
        desc.innerHTML      = html;
        desc.dataset.parsed = '1';
    }

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       PICKER DEVISE
    ════════════════════════════════════════════════════════ */
    function togglePicker(e, picker) {
        e.stopPropagation();
        var isVisible = picker.style.display === 'flex' || picker.style.display === 'block';
        /* Fermer tous les autres pickers ouverts */
        document.querySelectorAll('.pn-drawer-picker').forEach(function (p) {
            if (p !== picker) p.style.display = 'none';
        });
        picker.style.display = isVisible ? 'none' : 'block';
    }

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       BULLE PANIER — mise à jour dynamique sans rechargement
    ════════════════════════════════════════════════════════ */
    function _getNavCartCount() {
        var badge = document.querySelector('.nav__cart-badge');
        return badge ? (parseInt(badge.textContent, 10) || 0) : 0;
    }

    function _setNavCartCount(n) {
        var link = document.querySelector('.nav__links .nav__link .fa-shopping-cart');
        if (!link) return;
        var navLink = link.closest('.nav__link');
        if (!navLink) return;
        var badge = document.querySelector('.nav__cart-badge');
        if (n <= 0) {
            if (badge) badge.remove();
        } else {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nav__cart-badge';
                navLink.appendChild(badge);
            }
            badge.textContent = n;
        }
        var dropBadge = document.querySelector('.nav__drop-badge');
        if (dropBadge) {
            dropBadge.textContent = n;
        } else if (n > 0) {
            var cartDropItem = document.querySelector('.nav__account-dd .fa-shopping-cart');
            if (cartDropItem) {
                var dropItem = cartDropItem.closest('.nav__drop-item');
                if (dropItem && dropItem.tagName === 'A') {
                    var nb = document.createElement('span');
                    nb.className = 'nav__drop-badge';
                    nb.textContent = n;
                    dropItem.appendChild(nb);
                }
            }
        }
    }

    function _incrementNavCart(qty) {
        _setNavCartCount(_getNavCartCount() + (qty || 1));
    }

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       AJAX — AJOUTER AU PANIER
    ════════════════════════════════════════════════════════ */
    function _doAddItem(value, qty, dp, vp) {
        $.ajax({
            url:      STORE_URLS.addToCart,
            method:   'POST',
            data:     { value: value, qty: qty, dp: dp || 0, vp: vp || 0 },
            dataType: 'text',
            success: function (response) {
                if (response) {
                    _openCartConfirmModal(_lastItemName, _lastItemIcon);
                    _incrementNavCart(qty);
                } else {
                    location.reload();
                }
            },
            error: function () { location.reload(); }
        });
    }

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       MODAL CONFIRMATION PANIER
    ════════════════════════════════════════════════════════ */
    function _openCartConfirmModal(name, icon) {
        document.getElementById('pnCartConfirmName').textContent = name || '';
        var imgEl = document.getElementById('pnCartConfirmIcon');
        var check = document.querySelector('.pn-cart-modal-check');
        if (icon) {
            imgEl.src          = icon;
            imgEl.style.display = 'block';
            if (check) check.style.display = 'none';
        } else {
            imgEl.style.display = 'none';
            if (check) check.style.display = 'flex';
        }
        var overlay = document.getElementById('pnCartOverlay');
        overlay.style.display = 'flex';
        overlay.classList.add('open');
    }

    function closeCartConfirmModal() {
        var overlay = document.getElementById('pnCartOverlay');
        overlay.classList.remove('open');
        overlay.style.display = 'none';
    }
    function pnCartOverlayClick(e) { if (e.target === e.currentTarget) closeCartConfirmModal(); }

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       CLAVIER
    ════════════════════════════════════════════════════════ */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeImgModal(); closeQtyModal(); closeCartConfirmModal(); }
    });

    window.openImgModal          = openImgModal;
    window.closeImgModal         = closeImgModal;
    window.pnImgOverlayClick     = pnImgOverlayClick;
    window.openQtyModal          = openQtyModal;
    window.closeQtyModal         = closeQtyModal;
    window.pnOverlayClick        = pnOverlayClick;
    window.changeQty             = _changeQty;
    window.confirmAddItem        = confirmAddItem;
    window.toggleDrawer          = toggleDrawer;
    window.togglePicker          = togglePicker;
    window.closeCartConfirmModal = closeCartConfirmModal;
    window.pnCartOverlayClick    = pnCartOverlayClick;

    /* ════════════════════════════════════════════════════════
       SIDEBAR MOBILE — toggle accordéon (≤768px)
    ════════════════════════════════════════════════════════ */
    (function () {
        var sidebar = document.querySelector('.pn-store-sidebar');
        var sidebarHeader = sidebar && sidebar.querySelector('.pn-sidebar-header');
        if (!sidebar || !sidebarHeader) return;

        function isMobile() { return window.innerWidth <= 768; }

        function applyMobileState() {
            if (isMobile()) {
                sidebarHeader.setAttribute('role', 'button');
                sidebarHeader.setAttribute('tabindex', '0');
                sidebarHeader.setAttribute('aria-expanded', sidebar.classList.contains('sidebar-open'));
            } else {
                sidebar.classList.add('sidebar-open');
                sidebarHeader.removeAttribute('role');
                sidebarHeader.removeAttribute('tabindex');
                sidebarHeader.removeAttribute('aria-expanded');
            }
        }

        sidebarHeader.addEventListener('click', function () {
            if (!isMobile()) return;
            sidebar.classList.toggle('sidebar-open');
            sidebarHeader.setAttribute('aria-expanded', sidebar.classList.contains('sidebar-open'));
        });

        sidebarHeader.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sidebarHeader.click(); }
        });

        applyMobileState();
        window.addEventListener('resize', applyMobileState, { passive: true });
    })();

})();
