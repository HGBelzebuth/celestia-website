/* ══════════════════════════════════════════════════════════
   modifications/assets/js/modifications_raid.js
   Accordion boss + Scroll Spy (Nettoyé du vieux Sticky JS)
   ══════════════════════════════════════════════════════════ */

var _scrollingTo = false;

// ── Accordion ────────────────────────────────────────────
function closeAllBoss(except) {
    document.querySelectorAll('.pn-boss-card').forEach(function(card) {
        if (card !== except) card.classList.remove('is-open');
    });
}

function toggleBoss(header, index) {
    var card    = header.parentElement;
    var wasOpen = card.classList.contains('is-open');
    closeAllBoss(wasOpen ? null : card);
    card.classList.toggle('is-open');
    updateActiveNav(wasOpen ? -1 : index);
}

function openBoss(index) {
    var card = document.getElementById('boss-' + index);
    if (card) {
        closeAllBoss(card);
        card.classList.add('is-open');
        updateActiveNav(index);
        _scrollingTo = true;
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                var top = card.getBoundingClientRect().top + window.scrollY - 176;
                window.scrollTo({ top: top, behavior: 'smooth' });
                setTimeout(function() { _scrollingTo = false; }, 700);
            });
        });
    }
    return false;
}

// ── Nav active + scroll interne sidebar ──────────────────
function updateActiveNav(index) {
    document.querySelectorAll('.pn-sidebar a').forEach(function(a) {
        a.classList.remove('active');
    });
    if (index >= 0) {
        var nav = document.getElementById('nav-' + index);
        if (nav) {
            nav.classList.add('active');
            var navContainer = document.querySelector('.pn-sidebar-nav');
            if (navContainer) {
                var linkTop    = nav.offsetTop;
                var linkBottom = linkTop + nav.offsetHeight;
                var cTop       = navContainer.scrollTop;
                var cBottom    = cTop + navContainer.offsetHeight;
                if (linkTop < cTop + 20) {
                    navContainer.scrollTo({ top: linkTop - 20, behavior: 'smooth' });
                } else if (linkBottom > cBottom - 20) {
                    navContainer.scrollTo({ top: linkBottom - navContainer.offsetHeight + 20, behavior: 'smooth' });
                }
            }
        }
    }
}

// ── Scroll Spy (Surligne le menu au scroll) ───────────────
(function() {
    function onScroll() {
        if (_scrollingTo) return;
        
        var allCards = document.querySelectorAll('.pn-boss-card');
        var current  = -1;
        var scrollY  = window.scrollY || window.pageYOffset;
        var dynamicTop = 192; // Offset navbar

        var isAtBottom = (window.innerHeight + scrollY) >= document.body.scrollHeight - 10;
        
        if (isAtBottom && allCards.length > 0) {
            current = allCards.length - 1;
        } else {
            allCards.forEach(function(card, i) {
                if (card.getBoundingClientRect().top < dynamicTop + 60) {
                    current = i;
                }
            });
        }
        
        if(current !== -1) {
            updateActiveNav(current);
        }
    }

    function init() {
        var first = document.querySelector('.pn-boss-card');
        if (first && !first.classList.contains('is-open')) {
            first.classList.add('is-open');
            updateActiveNav(0);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// ── Scrollbar custom sidebar ─────────────────────────────
(function() {
    function initScrollbar() {
        var nav   = document.querySelector('.pn-sidebar-nav');
        var thumb = document.getElementById('pn-scrollbar-thumb');
        var track = document.querySelector('.pn-scrollbar-track');
        if (!nav || !thumb || !track) return;

        function updateThumb() {
            var trackH  = track.offsetHeight;
            var scrollH = nav.scrollHeight;
            var clientH = nav.clientHeight;
            if (scrollH <= clientH) { thumb.style.display = 'none'; return; }
            thumb.style.display  = 'block';
            var ratio      = clientH / scrollH;
            var thumbH     = Math.max(30, trackH * ratio);
            var scrollRatio = nav.scrollTop / (scrollH - clientH);
            thumb.style.height = thumbH + 'px';
            thumb.style.top    = (scrollRatio * (trackH - thumbH)) + 'px';
        }

        nav.addEventListener('scroll', updateThumb);
        window.addEventListener('resize', updateThumb);

        // Bloquer la propagation du scroll vers la page quand aux extrémités
        nav.addEventListener('wheel', function(e) {
            var atTop    = nav.scrollTop === 0;
            var atBottom = nav.scrollTop + nav.clientHeight >= nav.scrollHeight - 1;
            if ((atTop && e.deltaY < 0) || (atBottom && e.deltaY > 0)) {
                e.preventDefault();
            }
        }, { passive: false });

        updateThumb();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollbar);
    } else {
        initScrollbar();
    }
})();

/* ── Panneau décoratif : particules ─────────────────────── */
(function() {
    var container = document.getElementById('pn-deco-particles');
    if (container) {
        for (var i = 0; i < 18; i++) {
            var p = document.createElement('span');
            p.className = 'pn-deco-particle';
            var size  = Math.random() * 2 + 0.5;
            var left  = Math.random() * 100;
            var delay = Math.random() * 10;
            var dur   = Math.random() * 8 + 8;
            var blur  = Math.random() < 0.4 ? '0 0 6px rgba(0, 225, 255, 0.6)' : 'none';
            p.style.cssText = [
                'width:'             + size + 'px',
                'height:'            + size + 'px',
                'left:'              + left + '%',
                'bottom:'            + (Math.random() * -5) + '%',
                'background-color:'  + (blur !== 'none' ? '#fff' : 'rgba(0, 225, 255, 0.3)'),
                'box-shadow:'        + blur,
                'animation-duration:'+ dur + 's',
                'animation-delay:-'  + delay + 's',
            ].join(';');
            container.appendChild(p);
        }
    }
})();

/* ── Ajustement dynamique du panneau décoratif ──────────── */
(function() {
    function adjustDecoPanel() {
        var wrapper = document.querySelector('.pn-deco-wrapper');
        var hero    = document.querySelector('.pn-raid-hero');
        var footer  = document.querySelector('.site-footer');
        if (!wrapper || !hero) return;

        var navbarHeight = 86;

        var heroBottomInViewport = hero.getBoundingClientRect().bottom;
        var top = Math.max(navbarHeight, heroBottomInViewport);
        wrapper.style.top = top + 'px';

        if (footer) {
            var footerTopInViewport = footer.getBoundingClientRect().top;
            var bottomGap = Math.max(0, window.innerHeight - footerTopInViewport);
            wrapper.style.bottom = bottomGap + 'px';
        } else {
            wrapper.style.bottom = '0px';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            adjustDecoPanel();
            window.addEventListener('scroll', adjustDecoPanel, { passive: true });
            window.addEventListener('resize', adjustDecoPanel);
        });
    } else {
        adjustDecoPanel();
        window.addEventListener('scroll', adjustDecoPanel, { passive: true });
        window.addEventListener('resize', adjustDecoPanel);
    }
})();