<?php
/**
 * modifications/views/serveur.php
 *
 * Prépage de la section Serveur — sous-catégories.
 * CSS : assets/css/modifications_serveur.css
 */

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$lang = $this->uri->segment(1);
?>

<link rel="stylesheet" href="<?= base_url('assets/css/modifications_serveur.css?v='.time()) ?>">

<div class="pn-page-bg"></div>

<div class="srv-hero">
    <div class="srv-hero-bg" style="background-image: url('https://wow.zamimg.com/uploads/screenshots/normal/374073.jpg')"></div>
    <div class="srv-hero-overlay"></div>
    
    <div class="srv-hero-content cw-wrap">
        <div class="srv-hero-medallion"><i class="fas fa-server"></i></div>
        
        <div class="srv-breadcrumb">
            <a href="<?= base_url(e($lang) . '/modifications') ?>">
                <i class="fas fa-scroll"></i> Modifications
            </a>
            &nbsp;/&nbsp; Serveur
        </div>
        
        <h1><span>Serveur</span></h1>
        <p>Règles, taux et paramètres généraux de Celestia-WoW</p>
        
        <div style="margin-top: 20px;">
            <a href="<?= base_url(e($lang) . '/modifications') ?>" class="btn-epic">
                <i class="fas fa-arrow-left"></i> Vue d'ensemble
            </a>
        </div>
    </div>
</div>

<div class="srv-layout">
    <div class="srv-grid cw-wrap">
        <?php foreach ($sections as $sec): ?>

            <?php if ($sec['available']): ?>
                <a href="<?= base_url(e($lang) . '/' . e($sec['url'])) ?>" class="srv-card">
            <?php else: ?>
                <div class="srv-card srv-card--disabled">
            <?php endif; ?>

                <div class="srv-card-bg" style="background-image: url('<?= e($sec['image']) ?>')"></div>
                <div class="srv-card-overlay"></div>

                <div class="srv-card-body">
                    <div class="srv-card-top">
                        <div class="srv-card-icon">
                            <i class="<?= e($sec['icon']) ?>"></i>
                        </div>
                        <div class="srv-card-info">
                            <div class="srv-card-title"><?= e($sec['name']) ?></div>
                            <p class="srv-card-desc"><?= e($sec['description']) ?></p>
                        </div>
                    </div>

                    <div class="srv-card-footer">
                        <?php if ($sec['available']): ?>
                            <i class="fas fa-arrow-right srv-card-arrow"></i>
                        <?php else: ?>
                            <div class="srv-card-soon">Bientôt disponible</div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php if ($sec['available']): ?>
                </a>
            <?php else: ?>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
</div>