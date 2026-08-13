<?php
/**
 * modifications/views/home.php
 *
 * Page d'accueil des modifications du serveur.
 * CSS chargé via : modifications/assets/css/modifications_home.css
 */

// Raccourci pour l'échappement XSS
function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>

<link rel="stylesheet" href="<?= base_url('assets/css/modifications_home.css?v='.time()) ?>">

<div class="pn-page-bg"></div>

<div class="mod-hero">
    <div class="mod-hero-bg" style="background-image: url('https://wow.zamimg.com/uploads/screenshots/normal/582693.jpg');"></div>
    <div class="mod-hero-overlay"></div>
    
    <div class="mod-hero-content cw-wrap">
        <div class="mod-hero-medallion"><i class="fas fa-scroll"></i></div>
        <h1><span>Modifications</span> du serveur</h1>
        <p>Consultez les ajustements apportés au contenu de Celestia-WoW</p>
    </div>
</div>

<div class="mod-layout">
    <div class="mod-grid cw-wrap">
        <?php foreach ($categories as $cat): ?>

            <?php if ($cat['available']): ?>
                <a href="<?= base_url($this->uri->segment(1) . '/' . e($cat['url'])) ?>" class="mod-card">
            <?php else: ?>
                <div class="mod-card mod-card--disabled">
            <?php endif; ?>

                <div class="mod-card-bg" style="background-image: url('<?= e($cat['image']) ?>')"></div>
                <div class="mod-card-overlay"></div>

                <div class="mod-card-body">
                    <div class="mod-card-top">
                        <div class="mod-card-icon">
                            <i class="<?= e($cat['icon']) ?>"></i>
                        </div>
                        <div class="mod-card-info">
                            <div class="mod-card-title"><?= e($cat['name']) ?></div>
                            <p class="mod-card-desc"><?= e($cat['description']) ?></p>
                        </div>
                    </div>

                    <div class="mod-card-footer">
                        <?php if ($cat['available']): ?>
                            <div class="mod-card-count">
                                <span><?= (int) $cat['count'] ?></span>
                                <?= e($cat['label']) ?><?= $cat['count'] > 1 ? 's' : '' ?>
                            </div>
                            <i class="fas fa-arrow-right mod-card-arrow"></i>
                        <?php else: ?>
                            <div class="mod-card-soon">Bientôt disponible</div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php if ($cat['available']): ?>
                </a>
            <?php else: ?>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
</div>