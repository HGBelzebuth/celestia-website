<?php
/**
 * modifications/views/taux.php
 * CSS : assets/css/modifications_raid.css  (réutilisé)
 * JS  : assets/js/modifications_raid.js    (réutilisé)
 */
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
$lang = $this->uri->segment(1);
?>
<link rel="stylesheet" href="<?= base_url('assets/css/modifications_raid.css') ?>">
<div class="pn-page-bg"></div>
<div class="pn-raid-hero">
    <div class="pn-raid-hero-bg" style="background-image: url('https://wow.zamimg.com/uploads/screenshots/normal/374073.jpg')"></div>
    <div class="pn-raid-hero-content">
        <div class="pn-raid-breadcrumb">
            <a href="<?= base_url(e($lang) . '/modifications/serveur') ?>"><i class="fas fa-server"></i> Serveur</a>
            &nbsp;/&nbsp; Taux
        </div>
        <div class="pn-raid-title"><i class="fas fa-chart-line"></i> Taux</div>
        <div class="pn-raid-subtitle">Multiplicateurs d'expérience, de butin et de réputation du serveur.</div>
        <a href="<?= base_url(e($lang) . '/modifications/serveur') ?>" class="pn-back">
            <i class="fas fa-arrow-left"></i> Retour au serveur
        </a>
    </div>
</div>
<div class="pn-raid-layout">
    <nav class="pn-sidebar">
        <div class="pn-sidebar-header">
            <div class="pn-sidebar-header-bg" style="background-image: url('https://wow.zamimg.com/uploads/screenshots/normal/374073.jpg')"></div>
            <div class="pn-sidebar-title">
                <i class="fas fa-list"></i> Catégories
                <?php if (!empty($sections)): ?>
                    <span class="pn-sidebar-count"><?= count($sections) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="pn-sidebar-nav-wrap">
            <div class="pn-sidebar-nav">
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $i => $section): ?>
                        <a href="#boss-<?= (int)$i ?>" id="nav-<?= (int)$i ?>" onclick="return openBoss(<?= (int)$i ?>)">
                            <i class="<?= e($section['icon']) ?>"></i>
                            <?= e($section['name']) ?>
                            <span class="pn-sidebar-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="pn-no-changes-sidebar"><i class="fas fa-chart-line"></i> Aucun taux</div>
                <?php endif; ?>
            </div>
            <div class="pn-scrollbar-track"><div class="pn-scrollbar-thumb" id="pn-scrollbar-thumb"></div></div>
        </div>
    </nav>
    <div class="pn-content">
        <?php if (empty($sections)): ?>
            <div class="pn-vanilla-notice">
                <i class="fas fa-chart-line"></i>
                <p>Aucun taux n'a encore été renseigné.<br>Revenez bientôt.</p>
            </div>
        <?php else: ?>
            <?php foreach ($sections as $i => $section): ?>
                <div class="pn-boss-card" id="boss-<?= (int)$i ?>">
                    <div class="pn-boss-header" onclick="toggleBoss(this, <?= (int)$i ?>)">
                        <div class="pn-boss-header-bg" style="background-image: url('<?= e($section['image']) ?>')"></div>
                        <div class="pn-boss-name">
                            <i class="<?= e($section['icon']) ?>" style="margin-right:10px;font-size:1rem;opacity:0.7"></i>
                            <?= e($section['name']) ?>
                        </div>
                        <i class="fas fa-chevron-down pn-boss-toggle"></i>
                    </div>
                    <div class="pn-boss-body">
                        <?php foreach ($section['entries'] as $entry): ?>
                            <div class="pn-change-section">
                                <div class="pn-change-label"><?= e($entry['label']) ?></div>
                                <?php foreach ($entry['rows'] as $row): ?>
                                    <div class="pn-change-row"><?= e($row) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<!-- Panneau décoratif — fixed droite -->
<div class="pn-deco-wrapper">
<div class="pn-deco-panel">
    <div class="pn-deco-particles" id="pn-deco-particles"></div>
    <a href="<?= base_url() ?>" class="pn-deco-logo-link">
        <img src="<?= $template['location'] ?>assets/images/logo-celestien.png"
             alt="<?= $this->config->item('website_name') ?>"
             class="pn-deco-logo">
    </a>
    <div class="pn-deco-brand">
        <span class="pn-deco-brand-name"><?= $this->config->item('website_name') ?></span>
        <span class="pn-deco-brand-sub">Serveur privé World of Warcraft</span>
    </div>
    <div class="pn-deco-ornament">
        <span class="pn-deco-ornament-glyph">✦ ✦ ✦</span>
    </div>
    <p class="pn-deco-tagline">
        Entrez dans le royaume.<br>L'aventure vous attend.
    </p>
</div>
</div><!-- /pn-deco-wrapper -->
<script src="<?= base_url('assets/js/modifications_raid.js') ?>"></script>
