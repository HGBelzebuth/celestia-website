/* ══════════════════════════════════════════════════════════
   VOTE-INDEX.JS — Celestia-WoW — Mise à jour des couleurs
   ══════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    var VOTE_LABEL    = (window.VOTE_CONFIG && VOTE_CONFIG.voteLabel) || 'VOTER';
    var BASE_URL      = (window.VOTE_CONFIG && VOTE_CONFIG.baseUrl) || '';

    var POLL_INTERVAL = 5000;   
    var POLL_MAX      = 36;     

    var sortInterval = null;

    function showNotif(message, type) {
        type = type || 'warning';
        var icons = { success: 'far fa-check-circle', danger: 'far fa-times-circle', warning: 'fas fa-exclamation-circle' };

        var container = document.querySelector('.vt-alerts');
        if (!container) {
            container = document.createElement('div');
            container.className = 'vt-alerts';
            var grid = document.querySelector('.vt-grid');
            if (grid) grid.parentNode.insertBefore(container, grid);
        }

        var div = document.createElement('div');
        div.className = 'vt-alert vt-alert-' + type;
        div.innerHTML =
            '<i class="' + icons[type] + '"></i>' +
            '<span>' + message + '</span>' +
            '<button class="vt-alert-close"><i class="fas fa-times"></i></button>';

        div.querySelector('.vt-alert-close').addEventListener('click', function () {
            div.remove();
        });

        container.appendChild(div);

        setTimeout(function () {
            if (div.parentNode) div.remove();
        }, 6000);
    }

    function fetchWithTimeout(url, options, ms) {
        ms = ms || 10000;
        var controller = new AbortController();
        var timer = setTimeout(function () { controller.abort(); }, ms);
        options.signal = controller.signal;
        return fetch(url, options).finally(function () { clearTimeout(timer); });
    }

    var io = ('IntersectionObserver' in window)
        ? new IntersectionObserver(function (entries) {
              for (var i = 0; i < entries.length; i++) {
                  entries[i].target.classList.toggle('is-visible', entries[i].isIntersecting);
              }
          }, { threshold: 0.1 })
        : null;

    function initCard(card) {
        if (io) io.observe(card);

        var remaining = parseInt(card.dataset.remaining, 10) || 0;

        var bar      = card.querySelector('.vt-progress-bar');
        var wrap     = card.querySelector('.vt-countdown-wrap');
        var numbers  = wrap ? Array.prototype.slice.call(wrap.querySelectorAll('.uk-countdown-number'))    : [];
        var seps     = wrap ? Array.prototype.slice.call(wrap.querySelectorAll('.uk-countdown-separator')) : [];
        var cooldown = bar  ? (parseInt(bar.dataset.cooldown, 10) || 1) : 1;

        var cardTimer = null;

        function cdColor() {
            var r, g, b;
            if (remaining <= 0) {
                r = 0; g = 255; b = 136; 
            } else {
                var h = remaining / 3600;
                if (h < 1) {
                    r = 0; g = 225; b = 255; 
                } else if (h < 4) {
                    r = 0; g = 139; b = 163; 
                } else {
                    r = 148; g = 163; b = 184; 
                }
            }

            if (bar) {
                var pct = cooldown > 0
                    ? Math.max(0, Math.min(100, Math.round((1 - remaining / cooldown) * 100)))
                    : 100;
                bar.style.width = pct + '%';
                if (remaining <= 0) {
                    bar.style.background = 'linear-gradient(90deg,rgba(0,255,136,.6),rgba(0,255,136,.9))';
                } else if (pct > 75) {
                    bar.style.background = 'linear-gradient(90deg,rgba('+r+','+g+','+b+',.5),rgba('+r+','+g+','+b+',.85))';
                } else if (pct > 40) {
                    bar.style.background = 'linear-gradient(90deg,rgba(0,225,255,.5),rgba(0,225,255,.85))';
                } else {
                    bar.style.background = 'linear-gradient(90deg,rgba(148,163,184,.3),rgba(148,163,184,.6))';
                }
            }

            var col  = 'rgb('  + r + ',' + g + ',' + b + ')';
            var glow = 'rgba(' + r + ',' + g + ',' + b + ',.38)';
            for (var i = 0; i < numbers.length; i++) {
                numbers[i].style.color      = col;
                numbers[i].style.textShadow = '0 0 10px ' + glow;
            }
            for (var j = 0; j < seps.length; j++) {
                seps[j].style.color = 'rgba(' + r + ',' + g + ',' + b + ',.4)';
            }

            if (remaining > 0) {
                remaining--;
                cardTimer = setTimeout(cdColor, 1000);
            } else {
                cardTimer = null;
            }
        }

        cdColor();

        if ('MutationObserver' in window) {
            var mo = new MutationObserver(function (mutations) {
                for (var k = 0; k < mutations.length; k++) {
                    var removed = mutations[k].removedNodes;
                    for (var l = 0; l < removed.length; l++) {
                        if (removed[l] === card || (removed[l].contains && removed[l].contains(card))) {
                            if (cardTimer) clearTimeout(cardTimer);
                            mo.disconnect();
                        }
                    }
                }
            });
            if (card.parentNode) mo.observe(card.parentNode, { childList: true });
        }
    }

    function sortCards() {
        var grid = document.querySelector('.vt-grid');
        if (!grid) {
            if (sortInterval) { clearInterval(sortInterval); sortInterval = null; }
            return;
        }

        var cards = Array.prototype.slice.call(grid.querySelectorAll('.vt-card'));
        var before = cards.map(function (c) { return c.dataset.remaining; }).join(',');

        cards.sort(function (a, b) {
            return (parseInt(a.dataset.remaining, 10) || 0) - (parseInt(b.dataset.remaining, 10) || 0);
        });

        var after = cards.map(function (c) { return c.dataset.remaining; }).join(',');

        if (before === after) return;

        var isMobile = window.innerWidth <= 768;

        for (var i = 0; i < cards.length; i++) {
            if (!isMobile) {
                cards[i].style.transition = 'opacity .3s ease ' + (i * 0.05) + 's';
                cards[i].style.opacity    = '0';
            }
        }

        setTimeout(function () {
            var frag = document.createDocumentFragment();
            for (var j = 0; j < cards.length; j++) {
                cards[j].style.opacity    = '1';
                cards[j].style.transition = '';
                frag.appendChild(cards[j]);
            }
            grid.appendChild(frag);
        }, isMobile ? 0 : 300);
    }

    function initParticles(card) {
        if (card.querySelector('.vt-particle')) return; 

        var header  = card.querySelector('.vt-card-header');
        if (!header) return;
        var headerH = header.offsetHeight || 130;
        var frag    = document.createDocumentFragment();

        for (var i = 0; i < 16; i++) {
            var p       = document.createElement('div');
            p.className = 'vt-particle';
            var x     = (Math.random() * 80 + 10).toFixed(1);
            var y     = (Math.random() * headerH * 0.8 + headerH * 0.1).toFixed(0);
            var dur   = (Math.random() * 2.5 + 2).toFixed(2);
            var delay = (Math.random() * 4).toFixed(2);
            var drift = (Math.random() * 24 - 12).toFixed(1);
            p.style.cssText = '--x:'+x+'%;--y:'+y+'px;--dur:'+dur+'s;--delay:'+delay+'s;--drift:'+drift+'px;';
            frag.appendChild(p);
        }
        card.appendChild(frag);
    }

    function initDelegation() {
        var grid = document.querySelector('.vt-grid');
        if (!grid) return;

        grid.addEventListener('click', function (e) {
            var voteBtn = e.target.closest('.js-vote-btn');
            if (voteBtn) { handleVote(voteBtn); return; }
        });
    }

    function handleVote(btn) {
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ouverture…';

        var voteWin = null;
        var isTouchSafari = /iP(hone|od|ad)/.test(navigator.userAgent) ||
                            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        if (isTouchSafari) {
            voteWin = window.open('', '_blank');
            if (voteWin) {
                voteWin.document.write(
                    '<html><head><title>Vote...</title><style>' +
                    'body{background:#020508;color:#d0dde8;font-family:sans-serif;' +
                    'display:flex;align-items:center;justify-content:center;height:100vh;margin:0;font-size:1.1rem;}' +
                    '</style></head><body><span>Chargement du site de vote…</span></body></html>'
                );
            }
        }

        fetchWithTimeout(btn.dataset.voteUrl, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }, 10000)
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            if (data.status === 'pending_created' || data.status === 'credited') {
                if (voteWin) {
                    voteWin.location.href = data.vote_url;
                } else {
                    window.open(data.vote_url, '_blank');
                }
                window.location.href = BASE_URL;
            } else if (data.status === 'pending') {
                if (voteWin) voteWin.close();
                showNotif(data.message, 'warning');
                window.location.href = BASE_URL;
            } else if (data.status === 'cooldown') {
                if (voteWin) voteWin.close();
                showNotif(data.message, 'warning');
                window.location.reload();
            } else {
                if (voteWin) voteWin.close();
                showNotif('Une erreur inattendue est survenue. Réessaie dans quelques instants.', 'danger');
                btn.disabled  = false;
                btn.innerHTML = VOTE_LABEL;
            }
        })
        .catch(function (err) {
            if (voteWin) voteWin.close();
            var msg = (err && err.name === 'AbortError')
                ? 'Le serveur ne répond pas (timeout). Réessaie dans quelques instants.'
                : 'Impossible de contacter le serveur. Vérifie ta connexion et réessaie.';
            showNotif(msg, 'danger');
            btn.disabled  = false;
            btn.innerHTML = VOTE_LABEL;
        });
    }

    function initAutoPoll() {
        var pollers = document.querySelectorAll('.js-auto-verify');
        for (var pi = 0; pi < pollers.length; pi++) {
            (function (el) {
                var attempts   = 0;
                var elapsed    = 0;   
                var intervalId = null;
                var tickId     = null;

                tickId = setInterval(function () {
                    elapsed++;
                    var timerEl = el.querySelector('.vt-poll-timer');
                    if (timerEl) timerEl.textContent = elapsed + 's';
                }, 1000);

                function stop() {
                    if (intervalId) { clearInterval(intervalId); intervalId = null; }
                    if (tickId)     { clearInterval(tickId);     tickId     = null; }
                }

                function doCheck() {
                    attempts++;

                    fetchWithTimeout(el.dataset.verifyUrl, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }, 8000)
                    .then(function (r) {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(function (data) {
                        if (data.status === 'credited') {
                            stop();
                            el.innerHTML = '<i class="fas fa-check-circle"></i> Vote confirmé !';
                            el.classList.remove('vt-btn--polling');
                            el.classList.add('vt-btn--poll-ok');
                            setTimeout(function () { window.location.href = BASE_URL; }, 1400);

                        } else if (data.status === 'not_yet') {
                            
                        } else {
                            stop();
                            el.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erreur — recharge la page.';
                            el.classList.remove('vt-btn--polling');
                            el.classList.add('vt-btn--poll-error');
                        }
                    })
                    .catch(function () {
                        
                    });

                    if (attempts >= POLL_MAX) {
                        stop();
                        el.innerHTML = '<i class="fas fa-clock"></i> Expiration — <a href="' + el.dataset.voteUrl + '" target="_blank" style="color:inherit;text-decoration:underline;pointer-events:auto">voter à nouveau ?</a>';
                        el.classList.remove('vt-btn--polling');
                        el.classList.add('vt-btn--poll-error');
                    }
                }

                doCheck();
                intervalId = setInterval(doCheck, POLL_INTERVAL);
            })(pollers[pi]);
        }
    }

    var _lbPeriod    = 'daily';
    var _lbTimer     = null;
    var _lbResetEnd  = 0;
    var _lbTickTimer = null;

    function initLeaderboard() {
        var section = document.querySelector('.vt-lb-section');
        if (!section) return;

        var periodBar = section.querySelector('.vt-lb-period-bar');
        if (periodBar) {
            periodBar.addEventListener('click', function (e) {
                var btn = e.target.closest('.vt-lb-period-btn');
                if (!btn) return;
                var period = btn.dataset.period;
                if (period === _lbPeriod) return;
                _lbPeriod = period;
                periodBar.querySelectorAll('.vt-lb-period-btn').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                loadLeaderboard(period);
            });
        }

        loadLeaderboard(_lbPeriod);
    }

    function loadLeaderboard(period) {
        var podiumEl  = document.getElementById('vtLbPodium');
        var listEl    = document.getElementById('vtLbList');
        var rewardsEl = document.getElementById('vtLbRewards');
        
        // Sécurité pour éviter le blocage du JS
        if (!podiumEl || !listEl || !window.VOTE_CONFIG) return;

        podiumEl.innerHTML = '<div class="vt-lb-podium-loading"><i class="fas fa-spinner fa-spin"></i></div>';
        listEl.innerHTML   = '';

        var url = VOTE_CONFIG.lbUrl + '/' + period;

        fetchWithTimeout(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }, 10000)
        .then(function (r) { 
            if (!r.ok) throw new Error('HTTP ' + r.status); 
            return r.json(); 
        })
        .then(function (data) {

            if (rewardsEl) {
                rewardsEl.innerHTML = '';
                if (data.rewards && data.rewards.length > 0) {

                    var top3Rewards = data.rewards.filter(function (r) { return parseInt(r.rank_from, 10) <= 3; });
                    var restRewards = data.rewards.filter(function (r) { return parseInt(r.rank_from, 10) >  3; });

                    var tierClass = ['gold', 'silver', 'bronze'];

                    if (top3Rewards.length > 0) {
                        var top3Wrap = document.createElement('div');
                        top3Wrap.className = 'vt-lb-rewards-top3';
                        top3Rewards.forEach(function (r, idx) {
                            var chip = document.createElement('div');
                            var tier = tierClass[idx] || '';
                            chip.className = 'vt-lb-reward-chip vt-lb-reward-chip--' + tier;
                            var rankLabel = r.rank_from == r.rank_to ? '#' + r.rank_from : '#' + r.rank_from + '–' + r.rank_to;
                            chip.innerHTML = '<span class="vt-lb-reward-chip-rank">' + rankLabel + '</span>'
                                           + '<i class="fas fa-gift"></i>'
                                           + '<span class="vt-lb-reward-items">' + _rewardLines(r.label) + '</span>';
                            top3Wrap.appendChild(chip);
                        });
                        rewardsEl.appendChild(top3Wrap);
                    }

                    if (restRewards.length > 0) {
                        var restWrap = document.createElement('div');
                        restWrap.className = 'vt-lb-rewards-rest';
                        restRewards.forEach(function (r) {
                            var chip = document.createElement('div');
                            chip.className = 'vt-lb-reward-chip vt-lb-reward-chip--rest';
                            var rankLabel = r.rank_from == r.rank_to ? '#' + r.rank_from : '#' + r.rank_from + '–' + r.rank_to;
                            chip.innerHTML = '<span class="vt-lb-reward-chip-rank">' + rankLabel + '</span>'
                                           + '<i class="fas fa-gift"></i>'
                                           + '<span class="vt-lb-reward-items">' + _rewardLines(r.label) + '</span>';
                            restWrap.appendChild(chip);
                        });
                        rewardsEl.appendChild(restWrap);
                    }
                }
            }

            if (data.next_reset) {
                _lbResetEnd = data.next_reset * 1000;
                if (_lbTickTimer) clearInterval(_lbTickTimer);
                _lbTickTimer = setInterval(_lbTick, 1000);
                _lbTick();
            }

            var currentUserId = (window.VOTE_CONFIG && VOTE_CONFIG.currentUserId) ? VOTE_CONFIG.currentUserId : 0;

            if (!data.rows || data.rows.length === 0) {
                podiumEl.innerHTML = '<div class="vt-lb-empty"><i class="fas fa-ghost"></i> Aucun vote pour cette période.</div>';
                listEl.innerHTML   = '';
                return;
            }

            var podiumCfg = [
                { rank: 2, cls: 'silver', icon: 'fas fa-medal',  label: '2ème', height: 80  },
                { rank: 1, cls: 'gold',   icon: 'fas fa-crown',  label: '1er',  height: 110 },
                { rank: 3, cls: 'bronze', icon: 'fas fa-medal',  label: '3ème', height: 60  }
            ];
            var podiumHtml = '<div class="vt-lb-podium-stage">';
            podiumCfg.forEach(function (cfg) {
                var row    = data.rows[cfg.rank - 1];
                var isMe   = row && currentUserId && parseInt(row.userid, 10) === currentUserId;
                var reward = row ? _findRewardForRank(data.rewards, cfg.rank) : null;
                podiumHtml += '<div class="vt-lb-podium-slot vt-lb-podium-slot--' + cfg.cls + (cfg.rank === 1 ? ' is-first' : '') + '">';
                if (row) {
                    podiumHtml += '<div class="vt-lb-podium-avatar">'
                                + '<i class="' + cfg.icon + '"></i>'
                                + '</div>'
                                + '<div class="vt-lb-podium-name">' + _esc(row.username) + (isMe ? ' <span class="vt-lb-you">Vous</span>' : '') + '</div>'
                                + '<div class="vt-lb-podium-pts"><i class="vp-icon"></i> ' + _fmt(row.points) + ' pts</div>'
                                + (reward ? '<div class="vt-lb-podium-reward"><i class="fas fa-gift"></i><span class="vt-lb-reward-items">' + _rewardLines(reward.label) + '</span></div>' : '')
                                + '<div class="vt-lb-podium-bar" style="height:' + cfg.height + 'px">'
                                + '<span class="vt-lb-podium-rank">' + cfg.label + '</span>'
                                + '</div>';
                } else {
                    podiumHtml += '<div class="vt-lb-podium-avatar vt-lb-podium-avatar--empty"><i class="fas fa-user"></i></div>'
                                + '<div class="vt-lb-podium-name" style="opacity:.35">—</div>'
                                + '<div class="vt-lb-podium-bar vt-lb-podium-bar--empty" style="height:' + cfg.height + 'px">'
                                + '<span class="vt-lb-podium-rank">' + cfg.label + '</span>'
                                + '</div>';
                }
                podiumHtml += '</div>';
            });
            podiumHtml += '</div>';
            podiumEl.innerHTML = podiumHtml;

            var listHtml = '';
            data.rows.forEach(function (row, i) {
                var rank   = i + 1;
                if (rank <= 3) return;
                var isMe   = currentUserId && parseInt(row.userid, 10) === currentUserId;
                var reward = _findRewardForRank(data.rewards, rank);
                listHtml += '<div class="vt-lb-list-row' + (isMe ? ' vt-lb-list-row--me' : '') + '">'
                          + '<span class="vt-lb-lv-rank">' + rank + '</span>'
                          + '<span class="vt-lb-lv-name">' + _esc(row.username) + (isMe ? ' <span class="vt-lb-you">Vous</span>' : '') + '</span>'
                          + '<span class="vt-lb-lv-pts"><i class="vp-icon"></i> ' + _fmt(row.points) + '</span>'
                          + '<span class="vt-lb-lv-reward">' + (reward ? '<i class="fas fa-gift"></i><span class="vt-lb-reward-items vt-lb-reward-items--list">' + _rewardLines(reward.label) + '</span>' : '<span class="vt-lb-no-reward">—</span>') + '</span>'
                          + '</div>';
            });
            listEl.innerHTML = listHtml || '';
        })
        .catch(function (err) {
            // Sécurité : Ne laisse pas la roue tourner indéfiniment en cas d'erreur
            console.error("Erreur Classement:", err);
            podiumEl.innerHTML = '<div class="vt-lb-empty" style="color:#ff4444;">Erreur de chargement du classement.</div>';
        });
    }

    function _lbTick() {
        var el   = document.getElementById('vtLbTimer');
        if (!el) return;
        var diff = Math.max(0, _lbResetEnd - Date.now());
        var d    = Math.floor(diff / 86400000);
        var h    = Math.floor((diff % 86400000) / 3600000);
        var m    = Math.floor((diff % 3600000) / 60000);
        var s    = Math.floor((diff % 60000) / 1000);
        el.textContent = (d > 0 ? d + 'j ' : '') + _pad(h) + 'h ' + _pad(m) + 'min ' + _pad(s) + 's';
        if (diff === 0 && _lbTickTimer) { clearInterval(_lbTickTimer); _lbTickTimer = null; }
    }

    function _findRewardForRank(rewards, rank) {
        if (!rewards) return null;
        for (var i = 0; i < rewards.length; i++) {
            if (rank >= parseInt(rewards[i].rank_from, 10) && rank <= parseInt(rewards[i].rank_to, 10)) return rewards[i];
        }
        return null;
    }

    function _esc(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    
    function _rewardLines(str) {
        if (!str) return '';
        return String(str).split(',').map(function (item) {
            return '<span>' + _esc(item.trim()) + '</span>';
        }).join('');
    }
    function _pad(n) { return n < 10 ? '0' + n : String(n); }
    function _fmt(n) { return String(parseInt(n,10)).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }

    /* ── AJOUT : Initialisation de la sélection de perso par grille (SÉCURISÉE) ── */
    function initRewardCharSelect() {
        var gridEl  = document.getElementById('vtCharGrid');
        var saveBtn = document.getElementById('vtRewardCharSave');
        var msgEl   = document.getElementById('vtCharSelectMsg');
        var selectedChar = null;
        
        // Sécurité pour éviter le blocage
        if (!gridEl || !saveBtn || !window.VOTE_CONFIG) return;

        // Fetch des personnages avec la BONNE URL dynamique
        fetchWithTimeout(VOTE_CONFIG.charsUrl, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }, 8000)
        .then(function(r) { 
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json(); 
        })
        .then(function(data) {
            if (data.status === 'ok') {
                gridEl.innerHTML = '';
                if (!data.characters || data.characters.length === 0) {
                    gridEl.innerHTML = '<div class="vt-char-empty" style="color: #94a3b8; padding: 15px;">Aucun personnage trouvé sur ce compte.</div>';
                    return;
                }

                data.characters.forEach(function(c) {
                    var card = document.createElement('div');
                    card.className = 'vt-char-card' + (data.current_char === c.name ? ' is-active' : '');
                    card.dataset.name = c.name;
                    
                    if (data.current_char === c.name) selectedChar = c.name;

                    card.innerHTML = 
                        '<div class="vt-char-icons">' +
                            '<img src="' + c.race_icon + '" class="vt-char-icon" alt="Race">' +
                            '<img src="' + c.class_icon + '" class="vt-char-icon" alt="Classe">' +
                        '</div>' +
                        '<div class="vt-char-info">' +
                            '<span class="vt-char-name">' + c.name + '</span>' +
                            '<span class="vt-char-lvl">' + c.class_name + ' · Niveau ' + c.level + '</span>' +
                        '</div>' +
                        '<div class="vt-char-check"><i class="fas fa-check-circle"></i></div>';

                    card.addEventListener('click', function() {
                        document.querySelectorAll('.vt-char-card').forEach(function(el) { el.classList.remove('is-active'); });
                        card.classList.add('is-active');
                        selectedChar = c.name;
                        saveBtn.disabled = false;
                    });

                    gridEl.appendChild(card);
                });
                if (selectedChar) saveBtn.disabled = false;
            }
        })
        .catch(function(err) {
            // Sécurité : Ne laisse pas la roue tourner indéfiniment
            console.error("Erreur Personnages:", err);
            gridEl.innerHTML = '<div class="vt-char-empty" style="color:#ff4444; padding:15px;">Erreur lors du chargement de vos personnages.</div>';
        });

        // Enregistrement sécurisé
        saveBtn.addEventListener('click', function() {
            if (!selectedChar) return;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Format Formulaire Classique pour éviter les blocages de sécurité
            var formData = new URLSearchParams();
            formData.append('realm_id', 1);
            formData.append('charname', selectedChar);

            // Ajout du jeton CSRF de BlizzCMS s'il existe
            var csrfInput = document.querySelector('input[name="wow_csrf_token_name"]') || document.querySelector('input[name="csrf_token_name"]');
            if (csrfInput) {
                formData.append(csrfInput.name, csrfInput.value);
            } else if (typeof wow_csrf_token_name !== 'undefined' && typeof wow_csrf_hash !== 'undefined') {
                formData.append(wow_csrf_token_name, wow_csrf_hash);
            } else if (typeof csrf_token_name !== 'undefined' && typeof csrf_hash !== 'undefined') {
                formData.append(csrf_token_name, csrf_hash);
            }

            // Fetch vers l'URL dynamique (avec $lang) pour éviter le renvoi 301 et la perte des POST
            fetchWithTimeout(VOTE_CONFIG.saveUrl, { 
                method: 'POST', 
                body: formData.toString(),
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                }, 
                credentials: 'same-origin' 
            }, 8000)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer le choix';
                msgEl.textContent = data.message;
                msgEl.className = 'vt-char-msg ' + (data.status === 'ok' ? 'vt-char-msg--success' : 'vt-char-msg--error');
                setTimeout(function() { msgEl.textContent = ''; msgEl.className = 'vt-char-msg'; }, 4000);
            })
            .catch(function(err) {
                console.error("Erreur Sauvegarde:", err);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer le choix';
                msgEl.textContent = "Erreur de communication avec le serveur.";
                msgEl.className = 'vt-char-msg vt-char-msg--error';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var cards = document.querySelectorAll('.vt-card');
        for (var i = 0; i < cards.length; i++) {
            initCard(cards[i]);
            if (cards[i].classList.contains('is-double')) {
                initParticles(cards[i]);
            }
        }
        sortCards();
        sortInterval = setInterval(sortCards, 60000);
        initDelegation();
        initAutoPoll();
        initLeaderboard();
        
        // Appel de la fonction de sélection de perso
        initRewardCharSelect();
    });

})();