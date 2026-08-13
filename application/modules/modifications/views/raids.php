<?php
/**
 * modifications/views/raids.php
 *
 * Liste des raids du serveur.
 * CSS chargé via :
 * - assets/css/modifications_raids.css
 */

// Raccourci pour l'échappement XSS
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$lang = $this->uri->segment(1);
?>

<link rel="stylesheet" href="<?= base_url('assets/css/modifications_raids.css?v='.time()) ?>">

<div class="pn-page-bg"></div>

<div class="pn-raids-hero">
    <div class="pn-raids-hero-bg" style="background-image: url('https://wow.zamimg.com/uploads/screenshots/normal/582693.jpg');"></div>
    <div class="pn-raids-hero-overlay"></div>
    
    <div class="pn-raids-hero-content cw-wrap">
        <div class="pn-raids-hero-medallion"><i class="fas fa-dragon"></i></div>
        
        <div class="pn-raids-breadcrumb">
            <a href="<?= base_url(e($lang) . '/modifications') ?>">
                <i class="fas fa-scroll"></i> Modifications
            </a>
            &nbsp;/&nbsp; Raids
        </div>
        
        <h1 class="pn-raids-title"><span>Raids</span></h1>
        <div class="pn-raids-subtitle">Ajustements des boss, mécaniques et difficulté des raids</div>
        
        <div style="margin-top: 20px;">
            <a href="<?= base_url(e($lang) . '/modifications') ?>" class="btn-epic">
                <i class="fas fa-arrow-left"></i> Vue d'ensemble
            </a>
        </div>
    </div>
</div>

<div class="pn-raids-layout">
    <div class="pn-grid cw-wrap">
        <?php foreach ($raids as $slug => $raid): ?>
            <a href="<?= base_url(e($lang) . '/modifications/raid/' . e($slug)) ?>" class="pn-card">
                
                <div class="pn-short"><?= e($raid['short']) ?></div>
                
                <div class="pn-card-img-wrap">
                    <div class="pn-card-img" style="background-image: url('<?= e($raid['image']) ?>');"></div>
                </div>
                
                <div class="pn-card-body">
                    <div class="pn-card-title"><?= e($raid['name']) ?></div>
                    <div class="pn-card-desc"><?= e($raid['description']) ?></div>
                    
                    <div class="pn-card-footer">
                        <?php if ($raid['has_changes']): ?>
                            <span class="pn-badge pn-badge-modified">
                                <i class="fas fa-wrench"></i> Modifié
                            </span>
                        <?php else: ?>
                            <span class="pn-badge pn-badge-vanilla">
                                <i class="fas fa-check"></i> Vanilla
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
            </a>
        <?php endforeach; ?>
    </div>
</div>