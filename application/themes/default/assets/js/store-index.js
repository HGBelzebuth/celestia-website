/* ══════════════════════════════════════════════════════════
   STORE-INDEX.JS — Celestia-WoW — Page boutique principale
   ══════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    /* ── État global ── */
    var _pendingItemId  = null;
    var _baseDP         = 0;
    var _baseVP         = 0;
    var _priceType      = 0;
    var _lastItemName   = '';
    var _lastItemIcon   = '';

    /* ════════════════════════════════════════════════════
       BULLE PANIER — mise à jour dynamique sans rechargement
    ════════════════════════════════════════════════════ */

    function getCartBadge() {
        return document.querySelector('.nav__cart-badge');
    }

    function getCartCount() {
        var badge = getCartBadge();
        return badge ? (parseInt(badge.textContent, 10) || 0) : 0;
    }

    function setCartCount(n) {
        var link  = document.querySelector('.nav__links .nav__link .fa-shopping-cart');
        if (!link) return;
        var navLink = link.closest('.nav__link');
        if (!navLink) return;

        var badge = getCartBadge();

        if (n <= 0) {
            if (badge) badge.remove();
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'nav__cart-badge';
            navLink.appendChild(badge);
        }
        badge.textContent = n;

        /* Aussi mettre à jour le badge dans le dropdown compte */
        var dropBadge = document.querySelector('.nav__drop-badge');
        if (dropBadge) {
            dropBadge.textContent = n;
        } else {
            /* Créer le badge dans le dropdown si inexistant */
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

    function incrementCart(qty) {
        setCartCount(getCartCount() + (qty || 1));
    }


    /* ════════════════════════════════════════════════════
       HELPERS
    ════════════════════════════════════════════════════ */

    /** Met à jour le total affiché dans la modal quantité */
    function updateTotal(qty) {
        var el = document.getElementById('pnQtyTotalValue');
        if (!el) return;
        var parts = [];
        if (_priceType == 1 || _priceType == 3 || _priceType == 4) {
            parts.push('<i class="dp-icon"></i> ' + (_baseDP * qty));
        }
        if (_priceType == 2 || _priceType == 3 || _priceType == 4) {
            if (parts.length) parts.push('<span style="color:rgba(168,184,200,0.32);font-size:1rem;margin:0 2px">&</span>');
            parts.push('<i class="vp-icon"></i> ' + (_baseVP * qty));
        }
        el.innerHTML = parts.length ? parts.join('') : '—';
    }

    /* ════════════════════════════════════════════════════
       MODAL IMAGE (LOUPE)
    ════════════════════════════════════════════════════ */

    window.openImgModal = function (src, name) {
        document.getElementById('pnImgModalSrc').src = src;
        document.getElementById('pnImgModalName').textContent = name || '';
        var overlay = document.getElementById('pnImgOverlay');
        overlay.style.display = 'flex';
        overlay.offsetHeight; // force reflow pour l'animation
        overlay.classList.add('open');
    };

    window.closeImgModal = function () {
        var overlay = document.getElementById('pnImgOverlay');
        overlay.classList.remove('open');
        overlay.style.display = 'none';
    };

    window.pnImgOverlayClick = function (e) {
        if (e.target === e.currentTarget) closeImgModal();
    };

    /* ════════════════════════════════════════════════════
       MODAL QUANTITÉ
    ════════════════════════════════════════════════════ */

    window.openQtyModal = function (itemId, itemName, iconSrc, dp, vp, ptype) {
        _pendingItemId = itemId;
        _baseDP        = parseFloat(dp)  || 0;
        _baseVP        = parseFloat(vp)  || 0;
        _priceType     = parseInt(ptype) || 0;
        _lastItemName  = itemName || '';
        _lastItemIcon  = iconSrc  || '';

        document.getElementById('pnQtyValue').value = 1;
        document.getElementById('pnQtyTitle').textContent = itemName || '';

        var ic = document.getElementById('pnQtyIcon');
        ic.src          = iconSrc || '';
        ic.style.display = iconSrc ? 'block' : 'none';

        updateTotal(1);
        document.getElementById('pnQtyOverlay').style.display = 'flex';
    };

    window.closeQtyModal = function () {
        document.getElementById('pnQtyOverlay').style.display = 'none';
        _pendingItemId = null;
    };

    window.pnOverlayClick = function (e) {
        if (e.target === e.currentTarget) closeQtyModal();
    };

    window.changeQty = function (delta) {
        var input = document.getElementById('pnQtyValue');
        var val   = Math.max(1, Math.min(99, (parseInt(input.value) || 1) + delta));
        input.value = val;
        updateTotal(val);
    };

    window.confirmAddItem = function () {
        if (!_pendingItemId) return;
        var qty = Math.max(1, parseInt(document.getElementById('pnQtyValue').value) || 1);
        var id  = _pendingItemId, dp = _baseDP, vp = _baseVP;
        closeQtyModal();
        doAddItem(id, qty, dp, vp);
    };

    /* ════════════════════════════════════════════════════
       MODAL CONFIRMATION PANIER
    ════════════════════════════════════════════════════ */

    window.openCartConfirmModal = function (name, icon) {
        document.getElementById('pnCartConfirmName').textContent = name || '';
        var img = document.getElementById('pnCartConfirmIcon');
        var chk = document.querySelector('.pn-cart-modal-check');
        if (icon) {
            img.src          = icon;
            img.style.display = 'block';
            chk.style.display = 'none';
        } else {
            img.style.display = 'none';
            chk.style.display = 'flex';
        }
        var overlay = document.getElementById('pnCartOverlay');
        overlay.style.display = 'flex';
        overlay.classList.add('open');
    };

    window.closeCartConfirmModal = function () {
        var overlay = document.getElementById('pnCartOverlay');
        overlay.classList.remove('open');
        overlay.style.display = 'none';
    };

    window.pnCartOverlayClick = function (e) {
        if (e.target === e.currentTarget) closeCartConfirmModal();
    };

    /* ════════════════════════════════════════════════════
       AJAX — AJOUT AU PANIER
    ════════════════════════════════════════════════════ */

    function doAddItem(value, qty, dp, vp) {
        $.ajax({
            url:      STORE_URLS.addToCart,
            method:   'POST',
            data:     { value: value, qty: qty, dp: dp || 0, vp: vp || 0 },
            dataType: 'text',
            success: function (response) {
                if (response) {
                    openCartConfirmModal(_lastItemName, _lastItemIcon);
                    incrementCart(qty);
                } else {
                    location.reload();
                }
            },
            error: function () { location.reload(); }
        });
    }

    /* ════════════════════════════════════════════════════
       DRAWER — DESCRIPTION
    ════════════════════════════════════════════════════ */

    window.toggleDrawer = function (e, card) {
        e.stopPropagation();
        var isOpen = card.classList.contains('expanded');

        // Ferme les autres drawers ouverts dans la même grille
        var grid = card.closest('.pn-items-grid');
        (grid || document).querySelectorAll('.pn-item-card.expanded').forEach(function (c) {
            if (c !== card) c.classList.remove('expanded');
        });

        card.classList.toggle('expanded', !isOpen);

        // Lazy-parse la description à la première ouverture
        if (!isOpen) {
            var desc = card.querySelector('.pn-item-drawer-desc');
            if (desc && !desc.dataset.parsed) {
                var raw   = desc.getAttribute('data-raw') || '';
                var html  = '';
                if (raw) {
                    var lines = raw.split(/\$B|\n|\r\n/).map(function (l) { return l.trim(); }).filter(Boolean);
                    if (lines.length <= 1) {
                        var txt = (lines[0] || raw.trim()).replace(/(?<![a-zA-ZÀ-ÿ])(x\d+)(?![a-zA-ZÀ-ÿ])/gi, '<span class="pn-desc-qty">$1</span>');
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
                    desc.innerHTML = html;
                }
                desc.dataset.parsed = '1';
            }
        }
    };

    /* ════════════════════════════════════════════════════
       PICKER DEVISE
    ════════════════════════════════════════════════════ */

    window.togglePicker = function (e, picker) {
        e.stopPropagation();
        var isVisible = picker.style.display === 'flex' || picker.style.display === 'block';
        picker.style.display = isVisible ? 'none' : 'block';
    };

    /* ════════════════════════════════════════════════════
       INIT — ÉVÉNEMENTS
    ════════════════════════════════════════════════════ */

    document.addEventListener('DOMContentLoaded', function () {

        // Clic sur un bouton Ajouter (délégation)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.pn-add-btn');
            if (!btn) return;
            e.stopPropagation();
            e.preventDefault();
            openQtyModal(
                btn.dataset.id,
                btn.dataset.name,
                btn.dataset.icon,
                btn.dataset.dp   || 0,
                btn.dataset.vp   || 0,
                btn.dataset.type || 0
            );
        });

        // Saisie manuelle dans le champ quantité
        var qtyInput = document.getElementById('pnQtyValue');
        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                updateTotal(Math.max(1, Math.min(99, parseInt(this.value) || 1)));
            });
        }

        // Fermeture des modals au clavier
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeImgModal();
                closeQtyModal();
            }
        });

        // Fermeture du picker devise au clic extérieur
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.pn-drawer-picker') && !e.target.closest('.pn-item-card-footer-add--picker')) {
                document.querySelectorAll('.pn-drawer-picker').forEach(function (p) {
                    p.style.display = 'none';
                });
            }
        });

        /* ════════════════════════════════════════════════════
           SIDEBAR MOBILE — toggle accordéon (≤768px)
        ════════════════════════════════════════════════════ */
        var sidebar = document.querySelector('.pn-store-sidebar');
        var sidebarHeader = sidebar && sidebar.querySelector('.pn-sidebar-header');

        if (sidebar && sidebarHeader) {
            function isMobile() { return window.innerWidth <= 768; }

            function applyMobileState() {
                if (isMobile()) {
                    sidebarHeader.setAttribute('role', 'button');
                    sidebarHeader.setAttribute('tabindex', '0');
                    sidebarHeader.setAttribute('aria-expanded', sidebar.classList.contains('sidebar-open'));
                } else {
                    // Desktop : toujours ouvert
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
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sidebarHeader.click();
                }
            });

            applyMobileState();
            window.addEventListener('resize', applyMobileState, { passive: true });
        }

    });

})();
