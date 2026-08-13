<?php 
defined('BASEPATH') OR exit('No direct script access allowed'); 

/**
 * Correction du bug de l'onglet : 
 * On définit le titre proprement pour écraser toute erreur PHP parasite
 */
if(isset($this->template)) {
    $this->template->setTitle("Historique des achats");
}

// Empêche l'affichage des notices qui polluent le <title>
error_reporting(E_ALL & ~E_NOTICE); 
?>

<link rel="stylesheet" href="<?= base_url('assets/css/cart.css?v='.time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/history.css?v='.time()) ?>">

<div class="cart-page-bg"></div>

<div class="cart-hero">
    <div class="cart-hero-bg" style="background-image:url('<?= base_url('assets/core/css/images/entree_icc.png') ?>')"></div>
    <div class="cart-hero-content cw-wrap">
        <div class="cart-hero-medallion"><i class="fas fa-history"></i></div>
        <h1>Historique</h1>
        <p>Vos achats sur la boutique de Celestia</p>
        <div class="cart-hero-line"></div>
    </div>
</div>

<div class="cart-layout">
    <div class="cart-container cw-wrap">
        <div class="cart-panel">

            <div class="cart-panel-header">
                <div class="cart-panel-header-icon"><i class="fas fa-receipt"></i></div>
                <h5>Récapitulatif des achats</h5>
                <span class="cart-panel-header-badge"><?= isset($total) ? (int)$total : 0 ?> commande<?= (isset($total) && $total > 1) ? 's' : '' ?></span>
            </div>

            <?php if (empty($logs)): ?>
            <div class="cart-empty">
                <div class="cart-empty-icon"><i class="fas fa-shopping-basket"></i></div>
                <h3>Historique vide</h3>
                <p>Vos futurs achats apparaîtront ici.</p>
                <br><a href="<?= base_url('store') ?>" class="btn-epic" style="display:inline-flex;margin-top:10px;">Boutique</a>
            </div>

            <?php else: ?>
            <div class="hist-list">
                <?php foreach ($logs as $log):
                    // Nettoyage du nom de l'item (gestion des cadeaux)
                    $isGift    = strpos($log->item_name, '[GIFT') === 0;
                    $cleanName = $isGift ? preg_replace('/^\[GIFT[^\]]*\]\s*/', '', $log->item_name) : $log->item_name;
                    $giftTarget = '';
                    if ($isGift && preg_match('/^\[GIFT\xe2\x86\x92([^\]]+)\]/', $log->item_name, $m)) { $giftTarget = $m[1]; }
                    
                    $charLabel = isset($charNames[(int)$log->charid]) ? $charNames[(int)$log->charid] : 'Personnage inconnu';
                    
                    // Map des types d'achats pour les couleurs et icônes
                    $typeMap = [
                        1 => ['label' => 'Objet',     'icon' => 'fa-archive',    'cls' => 'type-item'],
                        2 => ['label' => 'Gold',      'icon' => 'fa-coins',      'cls' => 'type-gold'],
                        3 => ['label' => 'Niveau',    'icon' => 'fa-arrow-up',   'cls' => 'type-level'],
                        4 => ['label' => 'Service',   'icon' => 'fa-cog',        'cls' => 'type-service'],
                        5 => ['label' => 'Apparence', 'icon' => 'fa-magic',      'cls' => 'type-service'],
                        6 => ['label' => 'Faction',   'icon' => 'fa-shield-alt', 'cls' => 'type-service'],
                        7 => ['label' => 'Race',      'icon' => 'fa-dna',        'cls' => 'type-service'],
                    ];
                    $t = isset($typeMap[(int)$log->type]) ? $typeMap[(int)$log->type] : ['label' => 'Autre', 'icon' => 'fa-circle', 'cls' => 'type-item'];
                ?>
                <div class="hist-row">
                    <div class="hist-type-dot <?= $t['cls'] ?>">
                        <i class="fas <?= $t['icon'] ?>"></i>
                    </div>

                    <div class="hist-main">
                        <div class="hist-name"><?= htmlspecialchars($cleanName) ?></div>
                        <div class="hist-meta">
                            <?php if ($isGift && $giftTarget): ?>
                            <span class="hist-gift-pill"><i class="fas fa-gift"></i> Offert à <?= htmlspecialchars($giftTarget) ?></span>
                            <?php endif ?>
                            <span class="hist-char-label"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($charLabel) ?></span>
                        </div>
                    </div>

                    <div class="hist-date-col">
                        <div class="hist-date-day"><?= date('d/m/Y', (int)$log->date) ?></div>
                        <div class="hist-date-time"><?= date('H:i', (int)$log->date) ?></div>
                    </div>

                    <div class="hist-price-col">
                        <?php if ((int)$log->dp > 0): ?>
                        <span class="hist-price-tag"><i class="dp-icon"></i> <?= (int)$log->dp ?></span>
                        <?php endif ?>
                        <?php if ((int)$log->vp > 0): ?>
                        <span class="hist-price-tag"><i class="vp-icon"></i> <?= (int)$log->vp ?></span>
                        <?php endif ?>
                    </div>

                    <div class="hist-badge-col">
                        <span class="hist-badge <?= $t['cls'] ?>"><?= $t['label'] ?></span>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>

            <div class="cart-panel-footer">
                <a href="<?= base_url('store') ?>" class="cart-btn-back">
                    <i class="fas fa-arrow-left"></i> Retour Boutique
                </a>
                <div style="flex:1"></div>
                
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="hist-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>" class="hist-page-btn"><i class="fas fa-chevron-left"></i></a>
                    <?php endif ?>
                    
                    <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                        <a href="?page=<?= $p ?>" class="hist-page-btn <?= ((int)$p === (int)$page) ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?>" class="hist-page-btn"><i class="fas fa-chevron-right"></i></a>
                    <?php endif ?>
                </div>
                <?php endif ?>
            </div>

        </div>
    </div>
</div>