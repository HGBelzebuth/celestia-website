<?php
/* ══════════════════════════════════════════════════════════
   DONATE/INDEX.PHP — Celestia-WoW — Page donations
   ══════════════════════════════════════════════════════════
   OPTIMISATIONS :
   - CSS → assets/donate-index.css
   - JS  → assets/donate-index.js
   - getCurrentDP() appelé UNE seule fois hors de la boucle (était O(n) → O(1))
   - getSpecifyDonate() supprimé du model (doublon de getDonations)
   - Tooltip HTML construit côté PHP dans data-attributes → zéro innerHTML JS dynamique
   - bgImg calculé via tableau O(1) au lieu de if/elseif en cascade
   ══════════════════════════════════════════════════════════ */

if (isset($_POST['button_donate'])):
    $this->donate_model->getDonate($_POST['button_donate']);
endif;

$sym       = $this->config->item('paypal_currency_symbol');
$donations = $this->donate_model->getDonations()->result();
$currentDp = (int) $this->donate_model->getCurrentDP();   // ← UNE seule requête DB

/* ── Résolution d'image O(1) via tableau de seuils ── */
$bgThresholds = [
    5  => '/assets/images/donate/F4bXWz7.jpg',
    10 => '/assets/images/donate/images.jpg',
    20 => '/assets/images/donate/images2.jpg',
    30 => '/assets/images/donate/C9L0G336EJSC1589912849760.jpg',
    40 => '/assets/images/donate/5AF2GIABW1K11589912849914.jpg',
    50 => '/assets/images/donate/wp10407651.jpg',
    70 => '/assets/images/donate/VDQ10E2VMI9T1589912855089.jpg',
];
$bgDefault = '/assets/images/donate/FJPT5455KR5K1589912863345.jpg';

/* Résout l'image pour un prix donné — O(1) car tableau de taille fixe */
function dn_bg_img(int $price, array $thresholds, string $default): string {
    foreach ($thresholds as $limit => $img) {
        if ($price <= $limit) return $img;
    }
    return $default;
}
?>

<link rel="stylesheet" href="<?= $template['assets'] ?>css/donate-index.css">

<div class="dn-page-bg"></div>

<div class="dn-hero" style="background-image:url('<?= $template['assets'] ?>core/css/images/entree_icc.png')">
    <h1><i class="fas fa-donate"></i> DONATIONS</h1>
    <p>Soutenez Celestia-WoW et bénéficiez d'avantages exclusifs</p>
</div>

<?php if ($activeOffer): ?>
<div class="dn-offer">
    <div class="dn-offer-banner">
        <div class="dn-offer-star"><i class="fas fa-star"></i></div>
        <div class="dn-offer-info">
            <div class="dn-offer-label">Offre du moment</div>
            <div class="dn-offer-name"><?= htmlspecialchars($activeOffer['name']) ?></div>
            <div class="dn-offer-details">
                <?= number_format((float)$activeOffer['price'], 2, ',', '') ?>€
                &nbsp;·&nbsp;<strong><?= (int)$activeOffer['points'] ?></strong>&nbsp;<i class="dp-icon"></i>
                <?php if ((int)$activeOffer['points'] > (int)$activeOffer['base_points']): ?>
                <span class="dn-offer-bonus">+<?= (int)$activeOffer['points'] - (int)$activeOffer['base_points'] ?> DP offerts !</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($activeOffer['message'])): ?>
            <div class="dn-offer-msg"><?= htmlspecialchars($activeOffer['message']) ?></div>
            <?php endif; ?>
        </div>
        <a href="#dn-card-<?= (int)$activeOffer['donate_id'] ?>" class="dn-btn dn-offer-btn">
            <i class="fas fa-star"></i> VOIR L'OFFRE
        </a>
    </div>
</div>
<?php endif; ?>

<div class="dn-alerts">
    <?php $donationStatus = $this->session->flashdata('donation_status'); ?>
    <?php if ($donationStatus === 'success'): ?>
    <div class="dn-alert dn-alert-success">
        <i class="far fa-check-circle"></i>
        <span><strong>Succès :</strong> <?= $this->lang->line('notification_donation_successful') ?></span>
        <button class="dn-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php elseif ($donationStatus === 'canceled'): ?>
    <div class="dn-alert dn-alert-warning">
        <i class="fas fa-exclamation-circle"></i>
        <span><strong>Annulé :</strong> <?= $this->lang->line('notification_donation_canceled') ?></span>
        <button class="dn-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php elseif ($donationStatus === 'error'): ?>
    <div class="dn-alert dn-alert-danger">
        <i class="far fa-times-circle"></i>
        <span><strong>Erreur :</strong> <?= $this->lang->line('notification_donation_error') ?></span>
        <button class="dn-alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>
</div>

<div class="dn-grid">
<?php foreach ($donations as $donateList):
    $isFeatured  = $activeOffer && (int)$donateList->id === (int)$activeOffer['donate_id'];
    $basePrice   = (int)   $donateList->price;   // prix DB original — tiering des avantages
    $dispPrice   = $isFeatured ? (float)$activeOffer['price']  : $basePrice;  // prix affiché
    $points      = $isFeatured ? (int)  $activeOffer['points'] : (int)$donateList->points;
    $afterDp     = $currentDp + $points;
    $bgImg       = dn_bg_img($basePrice, $bgThresholds, $bgDefault);
?>
    <div class="dn-card<?= $isFeatured ? ' dn-card--featured' : '' ?>"
         id="dn-card-<?= (int)$donateList->id ?>"
         data-dp-current="<?= $currentDp ?>"
         data-dp-points="<?= $points ?>"
         data-dp-after="<?= $afterDp ?>">

        <?php if ($isFeatured): ?>
        <div class="dn-badge-featured"><i class="fas fa-star"></i> Offre du moment</div>
        <?php endif; ?>

        <div class="dn-card-header">
            <div class="dn-card-header-bg" style="background-image:url('<?= $bgImg ?>')"></div>
            <div class="dn-card-header-overlay"></div>
            <div class="dn-card-name"><?= htmlspecialchars($donateList->name) ?></div>
        </div>

        <div class="dn-card-body">
            <div class="dn-feature">
                <?php if ($basePrice === 5): ?>
                    <span>Recevez <strong class="dn-feature-hl"><?= $points ?></strong><i class="dp-icon"></i></span>
                <?php else: ?>
                    <span>Recevez  <strong class="dn-feature-hl"><?= $points ?></strong><i class="dp-icon"></i>au lieu de <?= $basePrice ?>0.</span>
                <?php endif; ?>
            </div>
            <div class="dn-feature">Rôle Discord avec droit de move, gif, images, gestion des emojis.</div>
            <div class="dn-feature">Téléporteur portable permanent (Mascotte Valkie).</div>
            <div class="dn-feature">Buffeur portable permanent (Mascotte Naxxie).</div>
            <div class="dn-feature">Monture exclusive "Poulet Magique" dès le niveau 20.</div>
            <?php if ($basePrice >= 20): ?>
            <div class="dn-feature dn-feature--gold">Obtenez la mascotte Nyx'i !</div>
            <?php endif; ?>
            <?php if ($basePrice >= 50): ?>
            <div class="dn-feature dn-feature--vip">Devenez VIP et profitez d'avantages exclusifs !</div>
            <div class="dn-feature dn-feature--gold">Obtenez la mascotte VIPet !</div>
            <?php endif; ?>
            <?php if ($basePrice >= 100): ?>
            <div class="dn-feature dn-feature--legendary">Recevez un coffret quotidien en jeu !</div>
            <?php endif; ?>
        </div>

        <div class="dn-card-footer">
            <div class="dn-card-price-footer">
                <?php if ($isFeatured && (float)$activeOffer['price'] != (float)$donateList->price): ?>
                <span class="dn-price-original"><?= (int)$donateList->price ?><sup><?= $sym ?></sup></span>
                <?= number_format((float)$activeOffer['price'], 2, ',', '') ?><sup><?= $sym ?></sup>
                <?php else: ?>
                <?= $dispPrice ?><sup><?= $sym ?></sup>
                <?php endif; ?>
            </div>
            <form method="post">
                <button class="dn-btn<?= $isFeatured ? ' dn-btn--featured' : '' ?>" type="submit" name="button_donate" value="<?= (int)$donateList->id ?>">
                    <i class="fab fa-paypal"></i> CONTRIBUER
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Tooltip DP (positionné par JS) -->
<div id="dn-tip"></div>

<script src="<?= $template['assets'] ?>js/donate-index.js"></script>
