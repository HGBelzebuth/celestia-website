/* ══════════════════════════════════════════════════════════
   CART.JS — Celestia-WoW (Version Finale)
   ══════════════════════════════════════════════════════════ */

var availableGiftItems = [];
var selectedGiftItems  = [];
var _giftCharName      = null;
var _giftCharGuid      = null;
var _giftCheckTimer    = null;
var _checkoutBtn = document.getElementById('button_checkout');
var _currentDp   = _checkoutBtn ? parseInt(_checkoutBtn.dataset.currentDp) || 0 : 0;

function getSafeAjaxData(extraData) {
    var data = extraData || {};
    if (typeof $.cookie === 'function' && $.cookie('csrf_cookie_name')) {
        data['csrf_token_name'] = $.cookie('csrf_cookie_name');
    }
    return data;
}

function calcTotalQty() {
    var total = 0;
    document.querySelectorAll('.cart-qty').forEach(function(i) { total += parseInt(i.value) || 0; });
    return total;
}

function updateHeaderDP(newDp) {
    document.querySelectorAll('.nav__points-val').forEach(function(el) {
        if (el.nextElementSibling && el.nextElementSibling.classList.contains('dp-icon')) {
            el.textContent = newDp;
        }
    });
}

function updateHeaderBadge(qty) {
    var badge = document.getElementById('cartBadge');
    if (qty > 0) {
        if (badge) badge.textContent = qty + ' article' + (qty > 1 ? 's' : '');
        else {
            var html = '<span class="cart-panel-header-badge" id="cartBadge">' + qty + ' article' + (qty > 1 ? 's' : '') + '</span>';
            document.querySelector('.cart-panel-header').insertAdjacentHTML('beforeend', html);
        }
    } else {
        if (badge) badge.remove();
    }

    var navLink = document.querySelector('.nav__links .nav__link .fa-shopping-cart');
    if (navLink) {
        var navLinkEl  = navLink.closest('.nav__link');
        var navBadge   = document.querySelector('.nav__cart-badge');
        if (qty <= 0) {
            if (navBadge) navBadge.remove();
        } else {
            if (!navBadge) {
                navBadge = document.createElement('span');
                navBadge.className = 'nav__cart-badge';
                navLinkEl.appendChild(navBadge);
            }
            navBadge.textContent = qty;
        }
    }

    var dropBadge = document.querySelector('.nav__drop-badge');
    if (dropBadge) {
        if (qty <= 0) dropBadge.remove();
        else dropBadge.textContent = qty;
    }
}

function getEmptyCartHtml() {
    return '<div class="cart-empty"><div class="cart-empty-icon"><i class="fas fa-shopping-cart"></i></div><h3>Votre panier est vide</h3><p>Découvrez nos nouveautés dans la boutique pour commencer.</p><br><a href="' + CART_URLS.store + '" class="btn-epic" style="display:inline-flex;margin-top:10px;">Aller à la boutique</a></div>';
}

function recalcTotals() {
    var totDp = 0, totVp = 0;
    var rows = document.querySelectorAll('.cart-table tbody tr:not(.removing)');
    
    rows.forEach(function(row) {
        var qtyInput = row.querySelector('.cart-qty');
        if (!qtyInput) return;
        var qty = parseInt(qtyInput.value) || 0;
        
        var selectType = row.querySelector('.cart-select');
        var isHybrid   = selectType !== null;
        var currentType = isHybrid ? selectType.value : null;

        var uDp = parseInt(row.dataset.unitDp) || 0;
        var uVp = parseInt(row.dataset.unitVp) || 0;

        if (isHybrid) {
            if (currentType === '1') totDp += uDp * qty;
            else if (currentType === '2') totVp += uVp * qty;
        } else {
            if (uDp > 0 && uVp > 0) { totDp += uDp * qty; totVp += uVp * qty; }
            else if (uDp > 0) { totDp += uDp * qty; }
            else if (uVp > 0) { totVp += uVp * qty; }
        }
    });

    var dpDisp = document.getElementById('totalDpDisplay');
    var vpDisp = document.getElementById('totalVpDisplay');
    if (dpDisp) dpDisp.textContent = totDp;
    if (vpDisp) vpDisp.textContent = totVp;

    if (_checkoutBtn) {
        _checkoutBtn.dataset.totalDp = totDp;
        _checkoutBtn.dataset.totalVp = totVp;
    }
}

function changeQuantity(rowid, delta, btn) {
    var input = document.querySelector('input[data-rowid="' + rowid + '"]');
    if (!input) return;
    var newVal = parseInt(input.value) + delta;
    if (newVal < 1) newVal = 1;
    if (newVal > 99) newVal = 99;
    input.value = newVal;

    btn.disabled = true;
    $.ajax({
        url: CART_URLS.updateQuantity,
        type: "GET",
        data: { rowid: rowid, qty: newVal },
        dataType: "text",
        success: function(r) {
            btn.disabled = false;
            updateHeaderBadge(calcTotalQty());
            recalcTotals();
        }
    });
}
function incrementQuantity(rowid, btn) { changeQuantity(rowid, 1, btn); }
function decrementQuantity(rowid, btn) { changeQuantity(rowid, -1, btn); }

function updatePrice(sel, id, rowid) {
    $.ajax({
        url: CART_URLS.updatePrice,
        type: 'GET',
        data: { id: id, rowid: rowid, pricechoosed: sel.value },
        dataType: "text",
        success: function() { location.reload(); }
    });
}

function updateCharacter(sel, rowid) {
    $.ajax({
        url: CART_URLS.updateCharacter,
        type: 'GET',
        data: { rowid: rowid, char: sel.value },
        dataType: "text"
    });
}

function deleteItem(e, rowid) {
    e.preventDefault();
    var row = document.getElementById('row_' + rowid);
    if (row) row.classList.add('removing');

    $.ajax({
        url: CART_URLS.delete,
        method: "POST",
        data: getSafeAjaxData({ value: rowid }), 
        dataType: "text",
        success: function() {
            if (row) row.remove();
            var newQty = calcTotalQty();
            updateHeaderBadge(newQty);
            if (newQty === 0) { document.querySelector('.cart-panel').innerHTML = getEmptyCartHtml(); }
            else { recalcTotals(); }
            if (typeof CART_GIFT_ITEMS !== 'undefined') { CART_GIFT_ITEMS = CART_GIFT_ITEMS.filter(function(i) { return i.rowid !== rowid; }); }
        }
    });
}

/* ════════════════════════════════════════════════════════════
   PAIEMENT
════════════════════════════════════════════════════════════ */
$('#button_checkout').on('click', function(e) {
    e.preventDefault();
    
    var missingChar = false;
    $('.cart-char-select').each(function() {
        if ($(this).val() === '0') missingChar = true;
    });

    if (missingChar) { 
        $('#cartCharAlert').addClass('active');
        return; 
    }

    var reqDp = parseInt($(this).data('total-dp')) || 0;
    var reqVp = parseInt($(this).data('total-vp')) || 0;
    var curDp = parseInt($(this).data('current-dp')) || 0;
    var curVp = parseInt($(this).data('current-vp')) || 0;

    renderFinanceCards(reqDp, reqVp, curDp, curVp);
    
    var btn = document.getElementById('cartConfirmPay');
    if (reqDp > curDp || reqVp > curVp) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-ban"></i> Fonds insuffisants';
        btn.style.background = 'rgba(255,77,77,0.2)';
        btn.style.color = '#ff4d4d';
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Payer';
        btn.style.background = '';
        btn.style.color = '';
    }
    
    $('#cartConfirmModal').addClass('active');
});

function closeConfirmModal() { $('#cartConfirmModal').removeClass('active'); }
function closeSuccessModal() { $('#cartSuccessModal').removeClass('active'); }

$('#cartConfirmPay').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');
    
    $.ajax({
        url: CART_URLS.checkout,
        method: "POST",
        data: getSafeAjaxData({ value: 1 }),
        dataType: "text",
        success: function(res) {
            if (res === 'true') {
                closeConfirmModal();
                updateHeaderBadge(0);
                $('#cartSuccessModal').addClass('active');
                
                var reqDp = parseInt(_checkoutBtn.dataset.totalDp) || 0;
                if (reqDp > 0) {
                    var newDp = _currentDp - reqDp;
                    if (newDp < 0) newDp = 0;
                    updateHeaderDP(newDp);
                }
            } else {
                alert("Erreur lors du paiement. " + res);
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Payer');
            }
        },
        error: function() {
            alert("Erreur réseau.");
            btn.prop('disabled', false).html('<i class="fas fa-check"></i> Payer');
        }
    });
});

function renderFinanceCards(reqDp, reqVp, curDp, curVp) {
    var container = document.getElementById('cartConfirmSummary');
    if (!container) return;
    var html = '<div class="cart-sum-finance">';
    if (reqDp > 0) {
        html += '<div class="csf-card"><div class="csf-card-head"><div class="csf-card-icon"><i class="dp-icon"></i></div><div class="csf-card-name">Donation Points</div></div><div class="csf-card-body"><div class="csf-card-row"><span class="csf-card-lbl">Solde actuel</span><span class="csf-card-num cur">' + curDp + '</span></div><div class="csf-card-row"><span class="csf-card-lbl">Coût total</span><span class="csf-card-num cost">-' + reqDp + '</span></div><div class="csf-card-row" style="border-top:1px dashed rgba(255,255,255,0.1); margin-top:5px; padding-top:10px;"><span class="csf-card-lbl after">Après achat</span><span class="csf-card-num after">' + (curDp - reqDp) + '</span></div></div></div>';
    }
    if (reqVp > 0) {
        html += '<div class="csf-card"><div class="csf-card-head"><div class="csf-card-icon"><i class="vp-icon"></i></div><div class="csf-card-name">Vote Points</div></div><div class="csf-card-body"><div class="csf-card-row"><span class="csf-card-lbl">Solde actuel</span><span class="csf-card-num cur">' + curVp + '</span></div><div class="csf-card-row"><span class="csf-card-lbl">Coût total</span><span class="csf-card-num cost">-' + reqVp + '</span></div><div class="csf-card-row" style="border-top:1px dashed rgba(255,255,255,0.1); margin-top:5px; padding-top:10px;"><span class="csf-card-lbl after">Après achat</span><span class="csf-card-num after">' + (curVp - reqVp) + '</span></div></div></div>';
    }
    html += '</div>';
    container.innerHTML = html;
}

/* ════════════════════════════════════════════════════════════
   CADEAUX MULTIPLES
════════════════════════════════════════════════════════════ */
function openGiftMultipleModal() {
    if (typeof CART_GIFT_ITEMS === 'undefined' || CART_GIFT_ITEMS.length === 0) return;
    availableGiftItems = JSON.parse(JSON.stringify(CART_GIFT_ITEMS));
    selectedGiftItems = [];
    _giftCharName = null; _giftCharGuid = null;
    var charInput = document.getElementById('giftCharInput');
    if (charInput) charInput.value = '';
    setGiftStatus('idle', '', '');
    document.getElementById('giftItemsMultiple').style.display = 'block';
    document.getElementById('giftRecipientBlock').style.display = 'block';
    document.getElementById('giftVpBlock').style.display = 'none';
    document.getElementById('giftAvailableSelector').classList.add('hidden');
    document.getElementById('giftRecipientBlock').classList.remove('visible');
    renderSelectedGiftItems();
    renderAvailableGiftItems();
    $('#giftOverlay').addClass('active');
}

function closeGiftModal() { $('#giftOverlay').removeClass('active'); }

function showAvailableItems() {
    renderAvailableGiftItems();
    document.getElementById('giftAvailableSelector').classList.remove('hidden');
}

function renderSelectedGiftItems() {
    var container = document.getElementById('giftSelectedItemsList');
    if (selectedGiftItems.length === 0) {
        container.innerHTML = '<div class="gift-no-items-multi">Aucun article sélectionné.</div>';
        updateTotalCost();
        return;
    }
    var html = '';
    selectedGiftItems.forEach(function(item, idx) {
        html += '<div class="gift-item-card-multi">' +
                '<div class="gift-item-icon-multi" style="background-image:url(' + item.iconUrl + ')"></div>' +
                '<div class="gift-item-details-multi">' +
                    '<div class="gift-item-name-multi">' + item.name + '</div>' +
                    '<div class="gift-item-meta-multi">' +
                        '<div class="gift-item-price-multi">' + item.dp + ' <i class="dp-icon"></i></div>' +
                        '<div class="gift-qty-control">' +
                            '<button class="gift-qty-btn minus" onclick="updateGiftItemQty(' + idx + ',-1)">−</button>' +
                            '<input class="gift-qty-input" type="text" value="' + item.qty + '" readonly>' +
                            '<button class="gift-qty-btn plus" onclick="updateGiftItemQty(' + idx + ',1)">+</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="gift-item-delete-multi" onclick="removeSelectedGiftItem(' + idx + ')"><i class="fas fa-trash"></i></div>' +
                '</div>';
    });
    container.innerHTML = html;
    updateTotalCost();
}

function renderAvailableGiftItems() {
    var container = document.getElementById('giftAvailableItemsList');
    if (availableGiftItems.length === 0) {
        container.innerHTML = '<div style="padding:10px; color:var(--store-text);">Tous les articles éligibles ont été sélectionnés.</div>';
        return;
    }
    var html = '';
    availableGiftItems.forEach(function(item, idx) {
        html += '<div class="gift-available-item">' +
                '<div class="gift-available-icon" style="background-image:url(' + item.iconUrl + ')"></div>' +
                '<div class="gift-available-details">' +
                    '<div class="gift-available-name">' + item.name + '</div>' +
                    '<div class="gift-available-meta">' +
                        '<span class="gift-available-price">' + item.dp + ' <i class="dp-icon"></i></span>' +
                        '<span>Dispo : ' + item.maxQty + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="gift-available-actions">' +
                    '<div class="gift-qty-selector">' +
                        '<button class="gift-qty-selector-btn minus" onclick="updateAvailableGiftQty(' + idx + ',-1)">−</button>' +
                        '<input class="gift-qty-selector-input" id="availGiftQty_' + idx + '" type="text" value="1" readonly>' +
                        '<button class="gift-qty-selector-btn plus" onclick="updateAvailableGiftQty(' + idx + ',1)">+</button>' +
                    '</div>' +
                    '<button class="gift-btn-select" onclick="addGiftItemWithQty(' + idx + ')">Ajouter</button>' +
                '</div>' +
                '</div>';
    });
    container.innerHTML = html;
}

function updateAvailableGiftQty(idx, delta) {
    var item = availableGiftItems[idx];
    var input = document.getElementById('availGiftQty_' + idx);
    var val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > item.maxQty) val = item.maxQty;
    input.value = val;
}

function recheckGiftCharIfNeed() {
    var input = document.getElementById('giftCharInput');
    if (input && input.value.trim().length >= 2) { $(input).trigger('input'); }
}

function addGiftItemWithQty(idx) {
    var item = availableGiftItems[idx];
    var qty = parseInt(document.getElementById('availGiftQty_' + idx).value);
    var newItem = Object.assign({}, item);
    newItem.qty = qty;
    selectedGiftItems.push(newItem);
    item.maxQty -= qty;
    if (item.maxQty <= 0) availableGiftItems.splice(idx, 1);
    renderSelectedGiftItems();
    renderAvailableGiftItems();
    if (availableGiftItems.length === 0) document.getElementById('giftAvailableSelector').classList.add('hidden');
    if (selectedGiftItems.length > 0) document.getElementById('giftRecipientBlock').classList.add('visible');
    recheckGiftCharIfNeed();
}

function updateGiftItemQty(idx, delta) {
    var selItem = selectedGiftItems[idx];
    var availIdx = availableGiftItems.findIndex(function(i) { return i.rowid === selItem.rowid; });
    var availItem = (availIdx !== -1) ? availableGiftItems[availIdx] : null;
    
    if (delta > 0) {
        if (availItem && availItem.maxQty > 0) {
            selItem.qty++;
            availItem.maxQty--;
            if (availItem.maxQty <= 0) availableGiftItems.splice(availIdx, 1);
        }
    } else {
        if (selItem.qty > 1) {
            selItem.qty--;
            if (availItem) availItem.maxQty++;
            else {
                var restoredItem = Object.assign({}, selItem);
                restoredItem.maxQty = 1;
                delete restoredItem.qty;
                availableGiftItems.push(restoredItem);
            }
        }
    }
    renderSelectedGiftItems();
    renderAvailableGiftItems();
    recheckGiftCharIfNeed();
}

function removeSelectedGiftItem(idx) {
    var selItem = selectedGiftItems[idx];
    var availIdx = availableGiftItems.findIndex(function(i) { return i.rowid === selItem.rowid; });
    if (availIdx !== -1) { availableGiftItems[availIdx].maxQty += selItem.qty; } 
    else {
        var restoredItem = Object.assign({}, selItem);
        restoredItem.maxQty = selItem.qty;
        delete restoredItem.qty;
        availableGiftItems.push(restoredItem);
    }
    selectedGiftItems.splice(idx, 1);
    renderSelectedGiftItems();
    renderAvailableGiftItems();
    if (selectedGiftItems.length === 0) {
        document.getElementById('giftRecipientBlock').classList.remove('visible');
        _giftCharName = null; _giftCharGuid = null;
        document.getElementById('giftCharInput').value = '';
        setGiftStatus('idle', '', '');
        document.getElementById('giftBtnConfirm').disabled = true;
    }
    recheckGiftCharIfNeed();
}

function updateTotalCost() {
    var totalDp = 0;
    selectedGiftItems.forEach(function(item) { totalDp += item.dp * item.qty; });
    document.getElementById('giftTotalAmount').textContent = totalDp;
    checkGiftCanSubmit();
}

function setGiftStatus(state, msg, msgClass) {
    var wrap  = document.getElementById('giftInputWrap');
    var icon  = document.getElementById('giftStatusIcon');
    var ico   = document.getElementById('giftStatusIco');
    var msgEl = document.getElementById('giftStatusMsg');
    wrap.classList.remove('state-ok', 'state-error');
    icon.classList.remove('spinning', 'ok', 'error');
    ico.className = '';
    if      (state === 'loading') { icon.classList.add('spinning'); ico.className = 'fas fa-spinner fa-spin'; }
    else if (state === 'ok')      { wrap.classList.add('state-ok');    icon.classList.add('ok');    ico.className = 'fas fa-check-circle'; }
    else if (state === 'error')   { wrap.classList.add('state-error'); icon.classList.add('error'); ico.className = 'fas fa-times-circle'; }
    else { ico.className = 'fas fa-user'; }
    msgEl.className   = 'gift-status-msg' + (msg ? ' visible ' + msgClass : '');
    msgEl.textContent = msg || '';
}

$('#giftCharInput').on('input', function() {
    clearTimeout(_giftCheckTimer);
    var name = $(this).val().trim();
    if (name.length < 2) { 
        setGiftStatus('idle', '', ''); 
        _giftCharName = null; _giftCharGuid = null; checkGiftCanSubmit();
        return; 
    }
    setGiftStatus('loading', 'Vérification...', '');
    _giftCharName = null; _giftCharGuid = null; checkGiftCanSubmit();
    
    _giftCheckTimer = setTimeout(function() {
        if (selectedGiftItems.length === 0) { setGiftStatus('error', "Sélectionnez d'abord un article.", 'error'); return; }
        
        var itemId = selectedGiftItems[0].id;
        
        $.ajax({
            url: CART_URLS.checkGiftChar,
            method: 'POST',
            data: getSafeAjaxData({ item_id: itemId, char_name: name }),
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'ok') {
                    _giftCharName = resp.name; _giftCharGuid = resp.guid;
                    setGiftStatus('ok', '✓ ' + resp.name + ' trouvé !', 'ok');
                    checkGiftCanSubmit();
                } else {
                    var errorMsg = (resp.status === 'not_found') ? 'Personnage introuvable.' : (resp.msg === 'rate_limited' ? 'Trop de requêtes.' : 'Erreur.');
                    setGiftStatus('error', errorMsg, 'error');
                }
            },
            error: function() { setGiftStatus('error', 'Erreur réseau.', 'error'); }
        });
    }, 600);
});

function checkGiftCanSubmit() {
    var btn = document.getElementById('giftBtnConfirm');
    var totalDp = parseInt(document.getElementById('giftTotalAmount').textContent) || 0;
    if (selectedGiftItems.length > 0 && _giftCharGuid !== null && totalDp <= _currentDp) { btn.disabled = false; } else { btn.disabled = true; }
}

function confirmMultipleGifts() {
    if (selectedGiftItems.length === 0 || !_giftCharName || !_giftCharGuid) return;
    var totalDp = selectedGiftItems.reduce(function(sum, i) { return sum + i.dp * i.qty; }, 0);
    var curDp   = _currentDp;
    var html = '<div class="cart-sum-finance"><div class="csf-card"><div class="csf-card-head"><div class="csf-card-icon"><i class="dp-icon"></i></div><div class="csf-card-name">Donation Points</div></div><div class="csf-card-body"><div class="csf-card-row"><span class="csf-card-lbl">Solde actuel</span><span class="csf-card-num cur">' + curDp + '</span></div><div class="csf-card-row"><span class="csf-card-lbl">Coût</span><span class="csf-card-num cost">− ' + totalDp + '</span></div><div class="csf-card-row" style="border-top:1px dashed rgba(255,255,255,0.1); padding-top:10px; margin-top:5px;"><span class="csf-card-lbl after">Après envoi</span><span class="csf-card-num after">' + (curDp - totalDp) + '</span></div></div></div></div>';
    document.getElementById('giftConfirmSummary').innerHTML = html;
    document.getElementById('giftConfirmDest').innerHTML = 'Destinataire : <strong style="color:var(--store-ice)">' + _giftCharName + '</strong>';
    document.getElementById('giftConfirmModal').classList.add('active');
}

function closeGiftConfirmModal() { document.getElementById('giftConfirmModal').classList.remove('active'); }

function processMultipleGifts() {
    var btn = document.getElementById('giftConfirmPay');
    var savedCharName  = _giftCharName;
    btn.disabled       = true;
    btn.innerHTML      = '<i class="fas fa-circle-notch fa-spin"></i> Envoi en cours…';

    var itemsData = selectedGiftItems.map(function(item) { return { rowid: item.rowid, id: item.id, qty: item.qty }; });
    var realmId = selectedGiftItems[0].category;

    $.ajax({
        url: CART_URLS.sendMultipleGifts,
        method: 'POST',
        data: getSafeAjaxData({
            items: JSON.stringify(itemsData),
            char_guid: _giftCharGuid,
            char_name: _giftCharName,
            realm_id: realmId
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success || response.status === 'success' || response.status === 'ok') {
                closeGiftConfirmModal();
                closeGiftModal();
                var itemsList = selectedGiftItems.map(function(i) { return i.name + (i.qty > 1 ? ' (x' + i.qty + ')' : ''); }).join(', ');
                document.getElementById('cartSuccessTitle').textContent = 'Cadeaux envoyés !';
                document.getElementById('cartSuccessText').innerHTML = 'Vos cadeaux ont bien été envoyés à <strong>' + savedCharName + '</strong>.<br>Articles offerts : <span style="color:var(--store-ice)">' + itemsList + '</span>';
                document.getElementById('cartSuccessModal').classList.add('active');
                setTimeout(function() { location.reload(); }, 3500);
            } else {
                alert("Erreur: " + (response.message || "Erreur lors de l'envoi des cadeaux."));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Confirmer l\'envoi';
            }
        },
        error: function() {
            alert("Erreur réseau, impossible de joindre le serveur.");
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirmer l\'envoi';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var csdElements = document.querySelectorAll('.csd');
    csdElements.forEach(function(csd) {
        var trigger = csd.querySelector('.csd__trigger');
        var rowid   = csd.dataset.rowid;
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = csd.classList.contains('csd--open');
            document.querySelectorAll('.csd').forEach(function(el) { el.classList.remove('csd--open'); });
            if (!isOpen) csd.classList.add('csd--open');
        });
        var options = csd.querySelectorAll('.csd__option');
        options.forEach(function(opt) {
            opt.addEventListener('click', function(e) {
                e.stopPropagation();
                var guid = this.dataset.guid, raceIcon = this.dataset.raceicon, classIcon = this.dataset.classicon, name = this.dataset.name, realm = this.dataset.realm, className = this.dataset.classname, level = this.dataset.level;
                var realSelect = document.getElementById('sel_' + rowid);
                if (realSelect) { realSelect.value = guid; updateCharacter(realSelect, rowid); }
                trigger.innerHTML = '<img class="csd__icon csd__icon--race" src="' + raceIcon + '" alt=""><img class="csd__icon csd__icon--class" src="' + classIcon + '" alt=""><div class="csd__trigger-info"><span class="csd__name">' + name + '</span><span class="csd__sub">' + className + ' · Nv.' + level + ' · ' + realm + '</span></div><i class="fas fa-chevron-down csd__arrow"></i>';
                csd.querySelectorAll('.csd__option').forEach(function(o) { o.classList.remove('csd__option--selected'); var check = o.querySelector('.csd__check'); if (check) check.remove(); });
                this.classList.add('csd__option--selected'); this.insertAdjacentHTML('beforeend', '<i class="fas fa-check csd__check"></i>');
                csd.classList.remove('csd--open');
                var confirmBlock = document.getElementById('confirm_' + rowid);
                if (confirmBlock) { confirmBlock.style.display = 'block'; setTimeout(function() { confirmBlock.style.display = 'none'; }, 2000); }
            });
        });
    });
    document.addEventListener('click', function() { document.querySelectorAll('.csd').forEach(function(el) { el.classList.remove('csd--open'); }); });
});

document.addEventListener('DOMContentLoaded', function() {
    var applyBtn  = document.getElementById('couponApplyBtn');
    var input     = document.getElementById('couponInput');
    var removeBtn = document.getElementById('couponRemoveBtn');

    /* FIX: Message d'erreur élégant sans popup alert() */
    if (applyBtn && input) {
        applyBtn.addEventListener('click', function() {
            var code = input.value.trim().toUpperCase();
            var msgDiv = document.getElementById('couponMsg');
            
            if (!code) {
                if (msgDiv) { msgDiv.textContent = "Veuillez entrer un code."; msgDiv.className = 'coupon-msg error visible'; }
                return;
            }
            
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            $.ajax({
                url: CART_URLS.applyCoupon, 
                method: 'POST', 
                data: getSafeAjaxData({ code: code }), 
                dataType: 'json',
                success: function(r) { 
                    if (!r.success) { 
                        if (msgDiv) {
                            msgDiv.textContent = r.error || r.message || "Code promo invalide.";
                            msgDiv.className = 'coupon-msg error visible';
                        }
                        applyBtn.disabled = false; 
                        applyBtn.textContent = 'Appliquer'; 
                    } else { 
                        location.reload(); 
                    }
                },
                error: function() {
                    if (msgDiv) { msgDiv.textContent = "Erreur réseau."; msgDiv.className = 'coupon-msg error visible'; }
                    applyBtn.disabled = false; 
                    applyBtn.textContent = 'Appliquer'; 
                }
            });
        });
    }
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            $.ajax({ url: CART_URLS.removeCoupon, method: 'POST', data: getSafeAjaxData(), dataType: 'json', success: function(r) { if (r.success) location.reload(); } });
        });
    }
});