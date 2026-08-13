<?php
$_CI =& get_instance();
$prefill = htmlspecialchars($prefill_email ?? '', ENT_QUOTES);
$success = isset($msg) && $msg === 'success';
?>
<section class="pn-account-section">
  <div class="pn-account-container" style="max-width:480px;margin:80px auto;padding:0 16px">

    <div class="pn-account-box">
      <div class="pn-account-header" style="text-align:center;padding:32px 32px 0">
        <div style="font-size:2.5rem;margin-bottom:12px">✉️</div>
        <h2 style="margin:0 0 6px"><?= $success ? 'Email envoyé !' : 'Renvoyer l\'email de confirmation'; ?></h2>
      </div>

      <div class="pn-account-body" style="padding:24px 32px 32px">
        <?php if ($success): ?>
          <div class="alert alert-success" style="margin-bottom:20px">
            Si un compte en attente de confirmation existe pour cet email, un nouveau lien vient d'être envoyé (valable 24h).<br>
            Pensez à vérifier vos spams.
          </div>
          <a href="<?= base_url($lang . '/login') ?>" class="btn-epic" style="display:block;text-align:center">
            Se connecter au site
          </a>
        <?php else: ?>
          <?php if (!empty($prefill)): ?>
          <div class="alert alert-warning" style="margin-bottom:20px">
            Votre lien de confirmation a expiré. Entrez votre adresse email pour recevoir un nouveau lien.
          </div>
          <?php endif; ?>

          <form method="post" action="<?= base_url($lang . '/resend-confirm') ?>">
            <div class="form-group" style="margin-bottom:16px">
              <label for="email" style="display:block;margin-bottom:6px">Adresse email du compte</label>
              <input type="email" name="email" id="email" class="form-control"
                     value="<?= $prefill ?>" required
                     placeholder="votre@email.com"
                     style="width:100%;box-sizing:border-box">
            </div>
            <button type="submit" class="btn-epic" style="width:100%">
              Renvoyer le lien de confirmation
            </button>
          </form>

          <p style="text-align:center;margin-top:16px;font-size:.875rem;opacity:.7">
            <a href="<?= base_url($lang . '/register') ?>">Créer un nouveau compte</a>
            &nbsp;·&nbsp;
            <a href="<?= base_url($lang . '/login') ?>">Se connecter</a>
          </p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</section>
