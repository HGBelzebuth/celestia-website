/* ══════════════════════════════════════════════════════════
   DONATE-INDEX.JS — Celestia-WoW — Page donations
   ══════════════════════════════════════════════════════════
   OPTIMISATIONS :
   - Tooltip construit UNE seule fois avec un template fixe (innerHTML minimal)
   - Délégation d'événements mouseover/mouseout via data-attributes → O(1)
   - will-change géré par mouseenter/mouseleave (pas permanent)
   - IntersectionObserver pour les animations (paused → running)
   - Aucune boucle querySelectorAll répétée en runtime
   ══════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    /* ════════════════════════════════════════════════════
       TOOLTIP DP
       Positionnement via getBoundingClientRect — O(1)
       Contenu injecté UNE seule fois, mis à jour via textContent
       (pas de reconstruction innerHTML à chaque hover)
    ════════════════════════════════════════════════════ */

    var tip = document.getElementById('dn-tip');

    /* Pré-construire le squelette du tooltip une seule fois */
    if (tip) {
        tip.innerHTML =
            '<div class="dn-tip-row">'
                + '<span class="dn-tip-label">DP actuels</span>'
                + '<span class="dn-tip-val" id="dn-tip-cur"></span>'
            + '</div>'
            + '<div class="dn-tip-row dn-tip-row--sep">'
                + '<span class="dn-tip-label">DP du pack</span>'
                + '<span class="dn-tip-val dn-tip-val--blue" id="dn-tip-pts"></span>'
            + '</div>'
            + '<div class="dn-tip-row dn-tip-row--sep">'
                + '<span class="dn-tip-label">DP après achat</span>'
                + '<span class="dn-tip-val dn-tip-val--gold" id="dn-tip-after"></span>'
            + '</div>';

        /* Références directes aux cellules — accès O(1) sans re-query */
        var tipCur   = document.getElementById('dn-tip-cur');
        var tipPts   = document.getElementById('dn-tip-pts');
        var tipAfter = document.getElementById('dn-tip-after');
    }

    /* ════════════════════════════════════════════════════
       INTERSECTION OBSERVER — animations & is-visible
    ════════════════════════════════════════════════════ */

    var io = ('IntersectionObserver' in window)
        ? new IntersectionObserver(function (entries) {
              for (var i = 0; i < entries.length; i++) {
                  entries[i].target.classList.toggle('is-visible', entries[i].isIntersecting);
              }
          }, { threshold: 0.1 })
        : null;

    /* ════════════════════════════════════════════════════
       DÉLÉGATION — un seul listener sur .dn-grid
       Remplace N listeners individuels sur chaque bouton
    ════════════════════════════════════════════════════ */

    var grid = document.querySelector('.dn-grid');
    if (!grid) return;

    /* Observe chaque carte pour les animations */
    var cards = grid.querySelectorAll('.dn-card');
    if (io) {
        for (var c = 0; c < cards.length; c++) {
            io.observe(cards[c]);
        }
    }

    /* ── Délégation mouseover / mouseout sur la grille ──
       Désactivé sur appareils tactiles (pas de hover) */
    var isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

    if (!isTouchDevice) {
    grid.addEventListener('mouseover', function (e) {
        var btn = e.target.closest('.dn-btn');
        if (!btn || !tip) return;

        var card = btn.closest('.dn-card');
        if (!card) return;

        /* Activation will-change seulement pendant le survol */
        card.classList.add('is-hovered');

        /* Mise à jour textContent uniquement (pas de repaint de layout) */
        tipCur.textContent   = card.dataset.dpCurrent || '0';
        tipPts.textContent   = card.dataset.dpPoints  || '0';
        tipAfter.textContent = card.dataset.dpAfter   || '0';

        /* Positionnement — une seule lecture getBoundingClientRect */
        var rect         = btn.getBoundingClientRect();
        tip.style.left   = (rect.left + rect.width / 2 - tip.offsetWidth / 2) + 'px';
        tip.style.top    = (rect.top  - tip.offsetHeight - 10) + 'px';
        tip.style.opacity = '1';
    });

    grid.addEventListener('mouseout', function (e) {
        var btn = e.target.closest('.dn-btn');
        if (!btn) return;

        var card = btn.closest('.dn-card');
        if (card) card.classList.remove('is-hovered');

        if (tip) tip.style.opacity = '0';
    });

    } // end !isTouchDevice

})();
