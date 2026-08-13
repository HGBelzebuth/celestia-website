<?php
$cp        = $current_promo ?? null;
$settings  = $promo_settings ?? null;
$ttl_days  = $settings ? (int)$settings->promo_ttl_days : 7;
$pool      = $promo_pool ?? [];
?>
<section class="uk-section uk-section-xsmall" data-uk-height-viewport="expand: true">
  <div class="uk-container">

    <!-- Titre -->
    <div class="uk-grid uk-grid-small uk-margin-small" data-uk-grid>
      <div class="uk-width-expand uk-heading-line">
        <h3 class="uk-h3"><i class="fas fa-bolt"></i> Gestion de la Promo Boutique</h3>
      </div>
    </div>

    <div class="uk-grid uk-grid-small" data-uk-grid>

      <!-- Sidebar nav -->
      <div class="uk-width-1-4@s">
        <div class="uk-card uk-card-secondary">
          <ul class="uk-nav uk-nav-default">
            <li><a href="<?= base_url('admin/store'); ?>"><i class="fas fa-tags"></i> <?= $this->lang->line('section_store_categories'); ?></a></li>
            <li><a href="<?= base_url('admin/store/items'); ?>"><i class="fas fa-boxes"></i> <?= $this->lang->line('section_store_items'); ?></a></li>
            <li><a href="<?= base_url('admin/store/top'); ?>"><i class="fas fa-parachute-box"></i> <?= $this->lang->line('section_store_top'); ?></a></li>
            <li class="uk-active"><a href="<?= base_url('admin/store/promo'); ?>"><i class="fas fa-bolt"></i> Promo</a></li>
          </ul>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="uk-width-3-4@s">

        <!-- Promo en cours -->
        <div class="uk-card uk-card-default uk-card-body uk-margin-small-bottom">
          <h4 class="uk-card-title"><i class="fas fa-fire" style="color:#f0a500"></i> Promo actuellement active</h4>
          <?php if ($cp && !empty($cp['item_id'])): ?>
            <?php $expires_str = date('d/m/Y à H:i', $cp['expires_at']); ?>
            <div class="uk-grid uk-grid-small uk-flex-middle" data-uk-grid>
              <?php if (!empty($cp['item_name'])): ?>
              <div class="uk-width-expand">
                <p class="uk-margin-remove">
                  <strong><?= htmlspecialchars($cp['item_name']); ?></strong>
                  <span class="uk-label uk-label-warning uk-margin-small-left">-<?= (int)$cp['discount']; ?>%</span>
                </p>
                <p class="uk-margin-remove uk-text-muted uk-text-small">
                  <i class="fas fa-clock"></i> Expire le <?= $expires_str; ?>
                  &nbsp;·&nbsp; Item #<?= (int)$cp['item_id']; ?>
                </p>
              </div>
              <?php endif; ?>
              <div class="uk-width-auto">
                <a href="<?= base_url($lang.'/admin/store/promo/refresh'); ?>"
                   class="uk-button uk-button-danger uk-button-small"
                   onclick="return confirm('Réinitialiser la promo ? Un nouveau tirage sera effectué immédiatement.')">
                  <i class="fas fa-rotate"></i> Réinitialiser la promo
                </a>
              </div>
            </div>
          <?php else: ?>
            <p class="uk-text-muted">Aucune promo active pour le moment. La promo sera tirée automatiquement à la prochaine visite de la boutique.</p>
            <a href="<?= base_url($lang.'/admin/store/promo/refresh'); ?>"
               class="uk-button uk-button-primary uk-button-small">
              <i class="fas fa-rotate"></i> Forcer un tirage maintenant
            </a>
          <?php endif; ?>
        </div>

        <!-- Paramètres TTL -->
        <div class="uk-card uk-card-default uk-card-body uk-margin-small-bottom">
          <h4 class="uk-card-title"><i class="fas fa-sliders"></i> Durée de la promo</h4>
          <form action="<?= base_url($lang.'/admin/store/promo/settings/save'); ?>" method="post">
            <div class="uk-grid uk-grid-small uk-flex-middle" data-uk-grid>
              <div class="uk-width-auto">
                <label class="uk-form-label">Durée (jours)</label>
              </div>
              <div class="uk-width-small">
                <input class="uk-input uk-form-small" type="number" name="promo_ttl_days"
                       min="1" max="90" value="<?= $ttl_days; ?>" required>
              </div>
              <div class="uk-width-auto">
                <button type="submit" class="uk-button uk-button-primary uk-button-small">
                  <i class="fas fa-save"></i> Enregistrer
                </button>
              </div>
              <div class="uk-width-expand">
                <span class="uk-text-muted uk-text-small">
                  La promo actuelle ne sera pas interrompue — la nouvelle durée s'appliquera au prochain tirage.
                </span>
              </div>
            </div>
          </form>
        </div>

        <!-- Pool d'items -->
        <div class="uk-card uk-card-default uk-card-body">
          <h4 class="uk-card-title"><i class="fas fa-list"></i> Pool d'items promotionnels</h4>
          <p class="uk-text-muted uk-text-small uk-margin-small-bottom">
            Les items avec <strong>Promo activée</strong> entrent dans le tirage hebdomadaire.
            Le <strong>poids</strong> détermine la probabilité d'être tiré (1 = rare · 100 = très fréquent).
          </p>

          <div id="promo-msg" class="uk-margin-small-bottom" style="display:none"></div>

          <div class="uk-overflow-auto">
            <table class="uk-table uk-table-middle uk-table-divider uk-table-small uk-table-hover">
              <thead>
                <tr>
                  <th class="uk-width-small">Item</th>
                  <th>Nom</th>
                  <th class="uk-text-center uk-width-small">Prix</th>
                  <th class="uk-text-center uk-width-small">Promo</th>
                  <th class="uk-text-center uk-width-small">Poids</th>
                  <th class="uk-text-center uk-width-small">Sauvegarder</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pool as $item): ?>
                <?php $in_promo = (int)$item->promo === 1; ?>
                <tr id="row-<?= (int)$item->id; ?>" class="<?= $in_promo ? 'promo-active-row' : ''; ?>">
                  <td>
                    <?php if (!empty($item->icon)): ?>
                    <img src="https://wow.zamimg.com/images/wow/icons/medium/<?= htmlspecialchars(strtolower($item->icon)); ?>.jpg"
                         onerror="this.src='<?= base_url('assets/images/default_icon.jpg'); ?>'"
                         width="36" height="36" style="border-radius:4px;object-fit:cover">
                    <?php else: ?>
                    <span class="uk-text-muted">#<?= (int)$item->id; ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($item->name); ?></strong>
                    <span class="uk-text-muted uk-text-small" style="display:block">
                      <?php if ((int)$item->price_type === 1): ?>
                        <?= (int)$item->dp; ?> DP
                      <?php elseif ((int)$item->price_type === 2): ?>
                        <?= (int)$item->vp; ?> VP
                      <?php else: ?>
                        <?= (int)$item->dp; ?> DP / <?= (int)$item->vp; ?> VP
                      <?php endif; ?>
                    </span>
                  </td>
                  <td class="uk-text-center">
                    <?php if ((int)$item->price_type === 2): ?>
                      <span class="uk-text-muted" data-uk-tooltip="title: Les items VP uniquement sont exclus de la promo automatiquement">
                        <i class="fas fa-ban"></i> VP only
                      </span>
                    <?php else: ?>
                      <span><?= $in_promo
                        ? '<span class="uk-label uk-label-success">Active</span>'
                        : '<span class="uk-label">Inactive</span>'; ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="uk-text-center">
                    <?php if ((int)$item->price_type !== 2): ?>
                    <input type="checkbox" class="uk-checkbox promo-toggle"
                           data-id="<?= (int)$item->id; ?>"
                           <?= $in_promo ? 'checked' : ''; ?>>
                    <?php else: ?>
                    <input type="checkbox" class="uk-checkbox" disabled>
                    <?php endif; ?>
                  </td>
                  <td class="uk-text-center">
                    <input type="number" class="uk-input uk-form-small uk-form-width-xsmall rate-input"
                           data-id="<?= (int)$item->id; ?>"
                           min="1" max="100" value="<?= max(1, (int)$item->rate); ?>"
                           style="width:60px;text-align:center"
                           <?= (int)$item->price_type === 2 ? 'disabled' : ''; ?>>
                  </td>
                  <td class="uk-text-center">
                    <?php if ((int)$item->price_type !== 2): ?>
                    <button class="uk-button uk-button-default uk-button-small save-row-btn"
                            data-id="<?= (int)$item->id; ?>">
                      <i class="fas fa-save"></i>
                    </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /uk-width-3-4 -->
    </div><!-- /uk-grid -->
  </div>
</section>

<style>
.promo-active-row td:first-child { border-left: 3px solid #f0a500; }
</style>

<script>
(function() {
  var baseUrl = "<?= base_url($lang.'/admin/store/promo/toggle'); ?>";

  function showMsg(text, type) {
    var el = document.getElementById('promo-msg');
    el.className = 'uk-alert uk-alert-' + (type || 'success');
    el.innerHTML = '<i class="fas fa-' + (type === 'danger' ? 'exclamation-triangle' : 'check') + '"></i> ' + text;
    el.style.display = 'block';
    setTimeout(function() { el.style.display = 'none'; }, 3000);
  }

  function saveRow(id) {
    var row      = document.getElementById('row-' + id);
    var checkbox = row.querySelector('.promo-toggle');
    var rateInput = row.querySelector('.rate-input');
    if (!checkbox || !rateInput) return;

    var promo = checkbox.checked ? 1 : 0;
    var rate  = parseInt(rateInput.value, 10) || 50;

    var fd = new FormData();
    fd.append('item_id', id);
    fd.append('promo',   promo);
    fd.append('rate',    rate);

    fetch(baseUrl, { method: 'POST', body: fd })
      .then(function(r) { return r.text(); })
      .then(function(t) {
        if (t.trim() === 'OK') {
          // Mettre à jour la badge de statut
          var badge = row.querySelector('td:nth-child(3) span span');
          if (badge) {
            badge.className = promo ? 'uk-label uk-label-success' : 'uk-label';
            badge.textContent = promo ? 'Active' : 'Inactive';
          }
          // Mettre à jour la bordure colorée
          if (promo) { row.classList.add('promo-active-row'); }
          else        { row.classList.remove('promo-active-row'); }
          showMsg('Item #' + id + ' mis à jour.', 'success');
        } else {
          showMsg('Erreur lors de la sauvegarde.', 'danger');
        }
      })
      .catch(function() { showMsg('Erreur réseau.', 'danger'); });
  }

  document.querySelectorAll('.save-row-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      saveRow(this.dataset.id);
    });
  });
})();
</script>
