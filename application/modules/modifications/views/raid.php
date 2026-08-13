<?php
/**
 * modifications/views/raid.php
 *
 * Détail d'un raid — modifications et boss.
 * CSS : assets/css/modifications_raid.css
 * JS  : assets/js/modifications_raid.js
 */

// Raccourci pour l'échappement XSS
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$lang = $this->uri->segment(1);
?>

<link rel="stylesheet" href="<?= base_url('assets/css/modifications_raid.css?v='.time()) ?>">

<div class="pn-page-bg"></div>

<div class="pn-raid-hero">
    <div class="pn-raid-hero-bg" style="background-image: url('<?= e($raid['image']) ?>')"></div>
    <div class="pn-raid-hero-overlay"></div>
    
    <div class="pn-raid-hero-content cw-wrap">
        <div class="pn-raid-hero-medallion"><i class="fas fa-dungeon"></i></div>
        
        <div class="pn-raid-breadcrumb">
            <a href="<?= base_url(e($lang) . '/modifications/raids') ?>">
                <i class="fas fa-scroll"></i> Modifications
            </a>
            &nbsp;/&nbsp; <?= e($raid['name']) ?>
        </div>
        
        <h1 class="pn-raid-title"><span><?= e($raid['name']) ?></span></h1>
        <div class="pn-raid-subtitle"><?= e($raid['description']) ?></div>
        
        <div style="margin-top: 20px;">
            <a href="<?= base_url(e($lang) . '/modifications/raids') ?>" class="btn-epic">
                <i class="fas fa-arrow-left"></i> Retour aux raids
            </a>
        </div>
    </div>
</div>

<div class="pn-raid-layout cw-wrap">

    <nav class="pn-sidebar">
        <div class="pn-sidebar-header">
            <div class="pn-sidebar-header-bg" style="background-image: url('<?= e($raid['image']) ?>')"></div>
            <div class="pn-sidebar-title">
                <i class="fas fa-list"></i>
                Boss
                <?php if (!empty($raid['bosses'])): ?>
                    <span class="pn-sidebar-count"><?= count($raid['bosses']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="pn-sidebar-nav-wrap">
            <div class="pn-sidebar-nav">
                <?php if (!empty($raid['bosses'])): ?>
                    <?php foreach ($raid['bosses'] as $i => $boss): ?>
                        <?php $bgImg = isset($boss['bg']) ? $boss['bg'] : $raid['image']; ?>
                        <a href="#boss-<?= (int)$i ?>" id="nav-<?= (int)$i ?>" onclick="return openBoss(<?= (int)$i ?>)">
                            <span class="pn-sidebar-portrait" style="background-image: url('<?= e($bgImg) ?>')"></span>
                            <?= e($boss['name']) ?>
                            <span class="pn-sidebar-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="pn-no-changes-sidebar">
                        <i class="fas fa-shield-alt"></i>
                        Aucune modification
                    </div>
                <?php endif; ?>
            </div>
            <div class="pn-scrollbar-track">
                <div class="pn-scrollbar-thumb" id="pn-scrollbar-thumb"></div>
            </div>
        </div>
    </nav>

    <div class="pn-content">
        <?php if (empty($raid['bosses'])): ?>
            <div class="pn-vanilla-notice">
                <i class="fas fa-shield-alt"></i>
                <p>Ce raid n'a reçu aucune modification.<br>
                   Il fonctionne selon les mécaniques originales de WoW WotLK 3.3.5.</p>
            </div>
        <?php else: ?>
            <?php foreach ($raid['bosses'] as $i => $boss): ?>
                <?php $bgImg = isset($boss['bg']) ? $boss['bg'] : $raid['image']; ?>
                <div class="pn-boss-card" id="boss-<?= (int)$i ?>">
                    <div class="pn-boss-header" onclick="toggleBoss(this, <?= (int)$i ?>)">
                        <div class="pn-boss-header-bg" style="background-image: url('<?= e($bgImg) ?>')"></div>
                        <div class="pn-boss-name"><?= e($boss['name']) ?></div>
                        <i class="fas fa-chevron-down pn-boss-toggle"></i>
                    </div>
                    <div class="pn-boss-body">
                        <?php foreach ($boss['changes'] as $change): ?>
                            <div class="pn-change-section">
                                <div class="pn-change-label"><?= e($change['label']) ?></div>
                                <?php foreach ($change['rows'] as $row): ?>
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

<script src="<?= base_url('assets/js/modifications_raid.js?v='.time()) ?>"></script>