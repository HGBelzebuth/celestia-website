<?php
/* ══════════════════════════════════════════════════════════
   VOTE/INDEX.PHP — Celestia-WoW — Page de vote
   ══════════════════════════════════════════════════════════ */

$userid       = (int) $this->session->userdata('wow_sess_id');
$isDoubleWeek = isset($isDoubleWeek) ? $isDoubleWeek : $this->vote_model->isDoubleWeek();
$voteStatus   = $this->session->flashdata('vote_status');
$currentVP    = (int) $this->vote_model->getCredits($userid);
$now          = $this->wowgeneral->getTimestamp();

/* ── Préchargement bulk — 3 requêtes pour toutes les cartes ──────────────── */
$voteIds    = array_map(fn($v) => (int)$v->id, $voteList);
$expiredMap = $this->vote_model->getTimeLogExpiredBulk($voteIds, $userid);
$pendingMap = $this->vote_model->hasPendingVoteBulk($userid, $voteIds);
$urlMap     = $this->vote_model->getVoteUrlBulk($voteIds);
?>

<link rel="stylesheet" href="<?= base_url('assets/css/vote-index.css?v='.time()) ?>">

<div class="vt-page-bg"></div>

<div class="vt-hero" style="background-image:url('<?= htmlspecialchars($template['assets']) ?>core/css/images/980479.jpg')">
    <div class="vt-hero-overlay"></div>
    <div class="vt-hero-content cw-wrap">
        <div class="vt-hero-medallion"><i class="fas fa-star"></i></div>
        <h1><?= $this->lang->line('tab_vote') ?></h1>
        <p>Votez chaque jour et récoltez des Vote Points exclusifs</p>
    </div>
</div>

<?php if ($isDoubleWeek): ?>
<div class="vt-double-banner">
    <div class="vt-double-banner-inner">
        <i class="fas fa-star"></i>
        <span>Semaine double VP ! Tous vos votes rapportent <strong>2× plus de points</strong> jusqu'au 7 du mois.</span>
        <i class="fas fa-star"></i>
    </div>
</div>
<?php endif; ?>

<?php if ($voteStatus): ?>
<div class="vt-alerts">
    <?php if ($voteStatus === 'success'): ?>
    <div class="vt-alert vt-alert-success">
        <i class="far fa-check-circle"></i>
        <span><strong>Succès :</strong> <?= $this->lang->line('notification_vote_successful') ?></span>
        <button class="vt-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php elseif ($voteStatus === 'error'): ?>
    <div class="vt-alert vt-alert-danger">
        <i class="far fa-times-circle"></i>
        <span><strong>Erreur :</strong> <?= $this->lang->line('notification_vote_error') ?></span>
        <button class="vt-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php elseif ($voteStatus === 'pending'): ?>
    <div class="vt-alert vt-alert-warning">
        <i class="fas fa-spinner fa-spin"></i>
        <span><strong>En attente :</strong> Ton vote est en cours de vérification. Tes VP seront crédités automatiquement après confirmation.</span>
        <button class="vt-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="vt-grid">
<?php foreach ($voteList as $voteItem):
    $id            = (int) $voteItem->id;
    $expired       = isset($expiredMap[$id])  ? (int)  $expiredMap[$id]  : 0;
    $isPending     = isset($pendingMap[$id])  ? (bool) $pendingMap[$id]  : false;
    $voteUrl       = htmlspecialchars(isset($urlMap[$id]) ? $urlMap[$id] : '');
    $canVote       = $now >= $expired;
    $remaining     = max(0, $expired - $now);
    $gainVP        = (int) $voteItem->points;
    $totalCooldown = $voteItem->time * 60;
    $progressPct   = $totalCooldown > 0
                     ? max(0, min(100, round((1 - $remaining / $totalCooldown) * 100)))
                     : 100;

    $t = (int) $voteItem->time;
    $h = (int) floor($t / 60);
    $m = $t % 60;
    if ($h > 0 && $m > 0)  $cdLabel = $h.'h'.$m;
    elseif ($h > 0)         $cdLabel = $h.'h';
    else                    $cdLabel = $m.'min';

    $imgUrl = htmlspecialchars($voteItem->image);
?>
    <div class="vt-card<?= $isPending ? ' js-pending-card' : '' ?><?= $isDoubleWeek ? ' is-double' : '' ?>"
         data-remaining="<?= $remaining ?>">
        <div class="vt-card-side"></div>

        <div class="vt-site-pill"><?= htmlspecialchars($voteItem->name) ?></div>

        <div class="vt-card-header">
            <div class="vt-card-header-blur" style="background-image:url('<?= $imgUrl ?>')"></div>
            <div class="vt-card-header-bg"   style="background-image:url('<?= $imgUrl ?>')"></div>
            <div class="vt-card-header-overlay"></div>
        </div>

        <div class="vt-card-body">

            <div class="vt-points-block">
                <div class="vt-points-inner">
                    <div class="vt-points-val">
                        <?= $gainVP ?>
                        <?php if ($isDoubleWeek): ?>
                        <div class="vt-x2-coin-wrap">
                            <img src="<?= htmlspecialchars($template['assets']) ?>images/VP-coin.png" class="vt-x2-coin" alt="VP">
                            <div class="vt-x2-badge">×2</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="vt-points-label"><?= $this->lang->line('panel_vp') ?></div>
            </div>

            <div class="vt-sep-wrap">
                <div class="vt-sep-line"></div>
                <div class="vt-cooldown-badge">
                    <i class="fas fa-clock"></i><?= $cdLabel ?>
                </div>
            </div>

            <div class="vt-progress-wrap">
                <div class="vt-progress-bar"
                     data-pct="<?= $progressPct ?>"
                     data-remaining="<?= $remaining ?>"
                     data-cooldown="<?= $totalCooldown ?>">
                </div>
            </div>

            <div class="vt-countdown-wrap"
                 uk-grid
                 uk-countdown="date: <?= date('c', $expired) ?>">
                <div><div class="uk-countdown-number uk-countdown-hours"></div></div>
                <div class="uk-countdown-separator">:</div>
                <div><div class="uk-countdown-number uk-countdown-minutes"></div></div>
                <div class="uk-countdown-separator">:</div>
                <div><div class="uk-countdown-number uk-countdown-seconds"></div></div>
            </div>

        </div>

        <div class="vt-btn-wrap">
            <?php if ($isPending): ?>
            <div class="vt-btn vt-btn--polling js-auto-verify"
                 data-verify-url="<?= base_url('vote/verifyvote/'.$id) ?>"
                 data-vote-url="<?= $voteUrl ?>">
                <div class="vt-poll-row">
                    <div class="vt-poll-dots">
                        <span class="vt-poll-dot"></span>
                        <span class="vt-poll-dot"></span>
                        <span class="vt-poll-dot"></span>
                    </div>
                    <span class="vt-poll-label">Vérification en cours</span>
                </div>
                <span class="vt-poll-sub">VP crédités auto (<span class="vt-poll-timer">0s</span>)</span>
            </div>
            <a href="<?= $voteUrl ?>" target="_blank" class="vt-reopen-link">
                <i class="fas fa-external-link-alt"></i> Rouvrir la page de vote
            </a>
            <?php elseif ($canVote): ?>
            <button class="vt-btn js-vote-btn"
                    type="button"
                    data-vote-id="<?= $id ?>"
                    data-vote-url="<?= base_url('vote/votenow/'.$id) ?>">
                <i class="fas fa-vote-yea"></i><?= $this->lang->line('tab_vote') ?>
            </button>
            <?php else: ?>
            <button class="vt-btn" type="button" disabled>
                <i class="fas fa-clock"></i><?= $this->lang->line('tab_vote') ?>
            </button>
            <?php endif; ?>
        </div>

    </div>
<?php endforeach; ?>
</div>

<div class="vt-char-box">
    <div class="vt-char-box-header">
        <div class="vt-char-box-title">
            <i class="fas fa-user-shield"></i>
            <span>Réception des récompenses</span>
        </div>
        <button id="vtRewardCharSave" class="vt-btn vt-btn-save" disabled>
            <i class="fas fa-save"></i> Enregistrer le choix
        </button>
    </div>
    
    <p class="vt-char-box-desc">Sélectionnez le personnage qui recevra vos récompenses de classement par courrier en jeu :</p>
    
    <div id="vtCharGrid" class="vt-char-grid">
        <div class="vt-char-loading"><i class="fas fa-spinner fa-spin"></i> Chargement de vos héros...</div>
    </div>
    
    <div id="vtCharSelectMsg" class="vt-char-msg"></div>
</div>

<div class="vt-lb-section">

    <div class="vt-lb-header">
        <div class="vt-lb-title">
            <i class="fas fa-crown"></i>
            <span>Classement des Votes</span>
        </div>
        <div class="vt-lb-period-bar">
            <button class="vt-lb-period-btn active" data-period="daily">
                <i class="fas fa-sun"></i> Quotidien
            </button>
            <button class="vt-lb-period-btn" data-period="weekly">
                <i class="fas fa-calendar-week"></i> Hebdo
            </button>
            <button class="vt-lb-period-btn" data-period="monthly">
                <i class="fas fa-calendar-alt"></i> Mensuel
            </button>
        </div>
        <div class="vt-lb-timer-wrap">
            <i class="fas fa-hourglass-half"></i>
            <span>Reset dans&nbsp;:</span>
            <span class="vt-lb-timer" id="vtLbTimer">--</span>
        </div>
    </div>

    <div class="vt-lb-rewards" id="vtLbRewards"></div>

    <div class="vt-lb-podium" id="vtLbPodium">
        <div class="vt-lb-podium-loading"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <div class="vt-lb-list-wrap">
        <div class="vt-lb-list-header">
            <span class="vt-lb-lh-rank">Place</span>
            <span class="vt-lb-lh-name">Compte</span>
            <span class="vt-lb-lh-pts">Points</span>
            <span class="vt-lb-lh-reward">Récompense</span>
        </div>
        <div id="vtLbList" class="vt-lb-list">
            </div>
    </div>

</div>

<script>
<?php
// Extraction robuste et 100% sécurisée de la langue courante pour Javascript
$uri_lang = $this->uri->segment(1);
$valid_langs = ['fr', 'en', 'es', 'ru', 'de', 'pt'];
$lang_prefix = in_array($uri_lang, $valid_langs) ? $uri_lang . '/' : '';
?>
var VOTE_CONFIG = {
    voteLabel:     "<?= addslashes('<i class=\"fas fa-vote-yea\"></i> ' . $this->lang->line('tab_vote')) ?>",
    baseUrl:       "<?= base_url($lang_prefix . 'vote') ?>",
    lbUrl:         "<?= base_url($lang_prefix . 'vote/leaderboard_data') ?>",
    charsUrl:      "<?= base_url($lang_prefix . 'vote/characters/1') ?>",
    saveUrl:       "<?= base_url($lang_prefix . 'vote/saverewardchar') ?>",
    currentUserId: <?= (int)$this->session->userdata('wow_sess_id') ?>,
    assetsUrl:     "<?= base_url('assets/images') ?>"
};
</script>
<script src="<?= $template['assets'] ?>js/vote-index.js?v=2027"></script>