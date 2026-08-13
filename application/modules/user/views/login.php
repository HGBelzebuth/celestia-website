<style>
  /* ═══════════════════════════════════════════════════════════
   LOGIN PAGE — dark fantasy, palette argentée
   Hérite des tokens du layout.php
═══════════════════════════════════════════════════════════ */

  /* Tokens locaux (reprennent les vars du layout) */
  .cw-login {
    --silver: #c8d4e0;
    --silver-soft: #e2eaf2;
    --silver-dim: #8a9fb5;
    --silver-pale: rgba(180, 200, 220, .07);
    --silver-glow: rgba(180, 200, 220, .15);
    --silver-line: rgba(180, 200, 220, .18);

    --bg-deep: #020910;
    --bg-panel: rgba(6, 17, 28, .92);
    --bg-input: rgba(8, 22, 36, .85);

    --text-hi: #d8e4f0;
    --text-mid: #8da5bb;
    --text-lo: #4a6278;

    --border: rgba(180, 200, 220, .18);
    --border-lo: rgba(180, 200, 220, .09);

    --danger: #e05555;
    --success: #5dbe7a;

    --font-rune: 'Cinzel', Georgia, serif;
    --font-prose: 'Crimson Pro', Georgia, serif;

    --t: .3s;
    --ease-out: cubic-bezier(.22, 1, .36, 1);
  }

  /* ── Reset local ── */
  .cw-login *,
  .cw-login *::before,
  .cw-login *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  /* ── Conteneur plein écran ── */
  .cw-login {
    position: relative;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: var(--bg-deep);
    overflow: hidden;
  }

  /* Fond vidéo supprimé */

  /* Bruit de texture */
  .cw-login__noise {
    position: absolute;
    inset: 0;
    z-index: 1;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
    pointer-events: none;
    opacity: .5;
  }

  /* ════════════════════════════════════════════════════════════
   CÔTÉ GAUCHE — espace logo / identité
════════════════════════════════════════════════════════════ */
  .cw-login__identity {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: calc(var(--nav-h, 86px) + 2.5rem) 3rem 4rem;
    gap: 2.5rem;
    border-right: 1px solid var(--border-lo);
  }

  /* Lueur ambiante derrière le logo */
  .cw-login__identity::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 480px;
    height: 480px;
    background: radial-gradient(ellipse at center,
        rgba(180, 200, 220, .07) 0%,
        transparent 68%);
    pointer-events: none;
    animation: cw-pulse 5s ease-in-out infinite;
  }

  @keyframes cw-pulse {

    0%,
    100% {
      opacity: .6;
      transform: translate(-50%, -50%) scale(1);
    }

    50% {
      opacity: 1;
      transform: translate(-50%, -50%) scale(1.08);
    }
  }

  /* Ligne décorative supérieure + inférieure */
  .cw-login__identity::after {
    content: '✦';
    position: absolute;
    bottom: 2.5rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 1rem;
    color: var(--silver-dim);
    opacity: .4;
    letter-spacing: .3em;
    animation: cw-spin 20s linear infinite;
  }

  @keyframes cw-spin {
    from {
      transform: translateX(-50%) rotate(0deg);
    }

    to {
      transform: translateX(-50%) rotate(360deg);
    }
  }

  /* Logo */
  .cw-login__logo-link {
    display: block;
    position: relative;
    z-index: 1;
    animation: cw-float 6s ease-in-out infinite;
  }

  @keyframes cw-float {

    0%,
    100% {
      transform: translateY(0px);
    }

    50% {
      transform: translateY(-12px);
    }
  }

  .cw-login__logo-link img {
    width: clamp(160px, 22vw, 280px);
    height: auto;
    filter:
      drop-shadow(0 0 20px rgba(180, 200, 220, .25)) drop-shadow(0 0 60px rgba(180, 200, 220, .10));
    transition: filter var(--t);
  }

  .cw-login__logo-link:hover img {
    filter:
      drop-shadow(0 0 30px rgba(180, 200, 220, .45)) drop-shadow(0 0 80px rgba(180, 200, 220, .18));
  }

  /* Texte sous le logo */
  .cw-login__brand {
    position: relative;
    z-index: 1;
    text-align: center;
  }

  .cw-login__brand-name {
    display: block;
    font-family: var(--font-rune);
    font-size: 1.35rem;
    letter-spacing: .35em;
    text-transform: uppercase;
    color: var(--silver);
    line-height: 1;
    margin-bottom: .5rem;
  }

  .cw-login__brand-sub {
    display: block;
    font-family: var(--font-prose);
    font-size: 1rem;
    font-style: italic;
    color: var(--text-lo);
    letter-spacing: .12em;
  }

  /* Ligne d'ornement */
  .cw-login__ornament {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    max-width: 300px;
  }

  .cw-login__ornament::before,
  .cw-login__ornament::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(to right, transparent, var(--silver-line));
  }

  .cw-login__ornament::after {
    background: linear-gradient(to left, transparent, var(--silver-line));
  }

  .cw-login__ornament-glyph {
    font-size: 1rem;
    color: var(--silver-dim);
    letter-spacing: .2em;
  }

  /* Tagline */
  .cw-login__tagline {
    position: relative;
    z-index: 1;
    font-family: var(--font-prose);
    font-size: 1.05rem;
    font-style: italic;
    color: var(--text-mid);
    text-align: center;
    max-width: 280px;
    line-height: 1.6;
  }

  /* ════════════════════════════════════════════════════════════
   CÔTÉ DROIT — formulaire
════════════════════════════════════════════════════════════ */
  .cw-login__panel {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: clamp(2rem, 5vw, 5rem) clamp(2rem, 6vw, 5rem);
  }

  /* Carte de formulaire */
  .cw-login__card {
    width: 100%;
    max-width: 440px;
    background: var(--bg-panel);
    border: 1px solid var(--border-lo);
    border-radius: 16px;
    padding: clamp(2rem, 4vw, 3rem);
    position: relative;
    overflow: hidden;

    /* entrée */
    animation: cw-card-in .7s var(--ease-out) both;
  }

  @keyframes cw-card-in {
    from {
      opacity: 0;
      transform: translateY(24px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Lueur argentée en haut */
  .cw-login__card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg,
        transparent 0%,
        var(--silver-dim) 30%,
        var(--silver) 50%,
        var(--silver-dim) 70%,
        transparent 100%);
    border-radius: 16px 16px 0 0;
  }

  /* Coins décoratifs */
  .cw-login__card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 16px;
    background: radial-gradient(ellipse at 50% -20%,
        rgba(180, 200, 220, .06) 0%,
        transparent 60%);
    pointer-events: none;
  }

  /* En-tête de la carte */
  .cw-login__card-head {
    margin-bottom: 2rem;
    text-align: center;
  }

  .cw-login__card-title {
    font-family: var(--font-rune);
    font-size: 1.3rem;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: var(--silver);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .6rem;
    margin-bottom: .5rem;
  }

  .cw-login__card-title i {
    font-size: 1.1rem;
    color: var(--silver-dim);
  }

  .cw-login__card-sub {
    font-family: var(--font-prose);
    font-size: 1rem;
    font-style: italic;
    color: var(--text-lo);
    letter-spacing: .08em;
  }

  /* Séparateur */
  .cw-login__sep {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 1.5rem 0;
  }

  /* Alertes */
  .cw-login__alert {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: .9rem 1rem;
    border-radius: 8px;
    border: 1px solid;
    font-family: var(--font-prose);
    font-size: 1rem;
    line-height: 1.5;
    margin-bottom: 1.5rem;
    animation: cw-card-in .4s var(--ease-out) both;
  }

  .cw-login__alert i {
    flex-shrink: 0;
    margin-top: .15rem;
    font-size: 1.05rem;
  }

  .cw-login__alert--success {
    background: rgba(93, 190, 122, .08);
    border-color: rgba(93, 190, 122, .25);
    color: #7dd89a;
  }

  .cw-login__alert--success i {
    color: var(--success);
  }

  .cw-login__alert--danger {
    background: rgba(224, 85, 85, .08);
    border-color: rgba(224, 85, 85, .22);
    color: #e88;
  }

  .cw-login__alert--danger i {
    color: var(--danger);
  }

  /* Champ de formulaire */
  .cw-login__field {
    margin-bottom: 1.25rem;
  }

  .cw-login__field-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .cw-login__field-icon {
    position: absolute;
    left: 1rem;
    color: var(--silver-dim);
    font-size: 1rem;
    pointer-events: none;
    transition: color var(--t);
    z-index: 1;
  }

  .cw-login__input {
    width: 100%;
    padding: .85rem 1rem .85rem 2.8rem;
    background: var(--bg-input);
    border: 1px solid var(--border-lo);
    border-radius: 8px;
    font-family: var(--font-prose);
    font-size: 1.05rem;
    color: var(--text-hi);
    letter-spacing: .04em;
    outline: none;
    transition: border-color var(--t), box-shadow var(--t), background var(--t);
    -webkit-appearance: none;
  }

  .cw-login__input::placeholder {
    color: var(--text-lo);
  }

  .cw-login__input:focus {
    border-color: var(--silver-dim);
    background: rgba(8, 22, 36, .95);
    box-shadow: 0 0 0 3px rgba(180, 200, 220, .08), inset 0 1px 3px rgba(0, 0, 0, .3);
  }

  .cw-login__field-wrap:focus-within .cw-login__field-icon {
    color: var(--silver);
  }

  /* Rangée boutons */
  .cw-login__actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    margin-top: 1.75rem;
  }

  /* Lien mot de passe oublié */
  .cw-login__forgot {
    font-family: var(--font-rune);
    font-size: 1rem;
    letter-spacing: .1em;
    color: var(--text-lo);
    display: flex;
    align-items: center;
    gap: .4rem;
    transition: color var(--t);
    white-space: nowrap;
  }

  .cw-login__forgot:hover {
    color: var(--silver);
  }

  .cw-login__forgot i {
    font-size: 1rem;
  }

  /* Bouton connexion */
  .cw-login__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .85rem 2rem;
    width: 100%;
    font-family: var(--font-rune);
    font-size: 1rem;
    letter-spacing: .18em;
    text-transform: uppercase;
    background: transparent;
    color: var(--silver-soft);
    border: 1px solid var(--silver-dim);
    border-radius: 8px;
    cursor: pointer;
    transition: background var(--t), border-color var(--t), color var(--t), box-shadow var(--t);
    position: relative;
    overflow: hidden;
    white-space: nowrap;
  }

  /* Shimmer au hover */
  .cw-login__btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(255, 255, 255, .06) 50%,
        transparent 100%);
    transition: left .5s var(--ease-out);
  }

  .cw-login__btn:hover::before {
    left: 100%;
  }

  .cw-login__btn:hover {
    background: rgba(180, 200, 220, .1);
    border-color: var(--silver);
    color: var(--silver-soft);
    box-shadow: 0 0 20px rgba(180, 200, 220, .12);
  }

  .cw-login__btn:active {
    transform: scale(.98);
  }

  /* reCaptcha */
  .cw-login__captcha {
    margin-bottom: .75rem;
  }

  /* Lien inscription */
  .cw-login__register-hint {
    margin-top: 1.75rem;
    text-align: center;
    font-family: var(--font-prose);
    font-size: 1rem;
    font-style: italic;
    color: var(--text-lo);
  }

  .cw-login__register-hint a {
    color: var(--silver-dim);
    text-decoration: none;
    transition: color var(--t);
    font-style: normal;
    font-family: var(--font-rune);
    letter-spacing: .08em;
  }

  .cw-login__register-hint a:hover {
    color: var(--silver);
  }

  /* ════════════════════════════════════════════════════════════
   PARTICULES AMBIANTES
════════════════════════════════════════════════════════════ */
  .cw-login__particles {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
  }

  .cw-particle {
    position: absolute;
    width: 1px;
    height: 1px;
    border-radius: 50%;
    background: rgba(180, 200, 220, .5);
    animation: cw-rise linear infinite;
  }

  @keyframes cw-rise {
    0% {
      opacity: 0;
      transform: translateY(0) scale(1);
    }

    10% {
      opacity: 1;
    }

    90% {
      opacity: .4;
    }

    100% {
      opacity: 0;
      transform: translateY(-100vh) scale(0);
    }
  }

  /* ════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════ */
  @media (max-width: 900px) {
    .cw-login {
      grid-template-columns: 1fr;
      grid-template-rows: auto 1fr;
    }

    .cw-login__identity {
      border-right: none;
      border-bottom: 1px solid var(--border-lo);
      padding: calc(var(--nav-h, 86px) + 1.25rem) 2rem 1.5rem;
      min-height: unset;
      gap: 1rem;
    }

    .cw-login__identity::before {
      width: 200px;
      height: 200px;
    }

    .cw-login__logo-link img {
      width: clamp(90px, 22vw, 140px);
    }

    .cw-login__panel {
      padding: 2rem 1.5rem 3rem;
    }

    .cw-login__tagline {
      display: none;
    }

    .cw-login__brand {
      display: none;
    }

    .cw-login__ornament {
      display: none;
    }
  }
</style>

<!-- ═══════════════════════════════════════════════════════════
     FOND VIDÉO
═══════════════════════════════════════════════════════════ -->
<div class="cw-login">

  <div class="cw-login__noise"></div>

  <!-- ─── Particules ─── -->
  <div class="cw-login__particles" id="cwParticles"></div>

  <!-- ═══════════════════════════════════════════════════════════
     GAUCHE — identité / logo
═══════════════════════════════════════════════════════════ -->
  <div class="cw-login__identity">

    <a href="<?= base_url(); ?>" class="cw-login__logo-link">
      <img
        src="<?= $template['location'] . 'assets/images/logo-celestien.png'; ?>"
        alt="<?= $this->config->item('website_name'); ?>">
    </a>

    <div class="cw-login__brand">
      <span class="cw-login__brand-name"><?= $this->config->item('website_name'); ?></span>
      <span class="cw-login__brand-sub">Serveur privé World of Warcraft</span>
    </div>

    <div class="cw-login__ornament">
      <span class="cw-login__ornament-glyph">✦ ✦ ✦</span>
    </div>

    <p class="cw-login__tagline">
      Entrez dans le royaume.<br>L'aventure vous attend.
    </p>

  </div>


  <!-- ═══════════════════════════════════════════════════════════
     DROITE — formulaire
═══════════════════════════════════════════════════════════ -->
  <div class="cw-login__panel">
    <div class="cw-login__card">

      <!-- En-tête -->
      <div class="cw-login__card-head">
        <h1 class="cw-login__card-title">
          <i class="fas fa-sign-in-alt"></i>
          <?= lang('button_login'); ?>
        </h1>
        <span class="cw-login__card-sub">Connectez-vous à votre compte</span>
      </div>

      <!-- Alertes flash -->
      <?php if (isset($_GET['activated']) && $_GET['activated'] == '1'): ?>
        <div class="cw-login__alert cw-login__alert--success">
          <i class="far fa-circle-check"></i>
          <span><strong>Compte activé !</strong> Vous pouvez maintenant vous connecter.</span>
        </div>
      <?php endif; ?>
      <?php if ($this->session->flashdata('account_activation') == 'true'): ?>
        <div class="cw-login__alert cw-login__alert--success">
          <i class="far fa-circle-check"></i>
          <span><strong><?= lang('notification_valid_key'); ?></strong> — <?= lang('notification_valid_key_desc'); ?></span>
        </div>
      <?php elseif ($this->session->flashdata('account_activation') == 'false'): ?>
        <div class="cw-login__alert cw-login__alert--danger">
          <i class="far fa-circle-xmark"></i>
          <span><?= lang('notification_invalid_key'); ?></span>
        </div>
      <?php endif; ?>

      <?php if (isset($msg_email_not_verified)): ?>
        <div class="cw-login__alert cw-login__alert--warning" style="flex-direction:column;align-items:flex-start;gap:8px">
          <div><i class="fas fa-envelope"></i> <strong>Email non confirmé</strong></div>
          <span>Confirmez votre adresse email avant de vous connecter. Vérifiez votre boîte mail (et vos spams).</span>
          <a href="<?= base_url($lang . '/resend_confirm') ?>" style="color:inherit;text-decoration:underline;font-size:.875rem">Renvoyer l'email de confirmation</a>
        </div>
      <?php endif; ?>

      <?php if (isset($msg_error_login)): ?>
        <div class="cw-login__alert cw-login__alert--danger">
          <i class="far fa-circle-xmark"></i>
          <span><?= $msg_error_login; ?></span>
        </div>
      <?php endif; ?>

      <!-- Formulaire -->
      <?= form_open(current_url()); ?>

      <!-- Identifiant -->
      <div class="cw-login__field">
        <div class="cw-login__field-wrap">
          <i class="fas fa-user cw-login__field-icon"></i>
          <input
            class="cw-login__input"
            name="username"
            id="login_username"
            type="text"
            placeholder="<?= lang('placeholder_username'); ?>"
            autocomplete="username"
            required>
        </div>
      </div>

      <!-- Mot de passe -->
      <div class="cw-login__field">
        <div class="cw-login__field-wrap">
          <i class="fas fa-lock cw-login__field-icon"></i>
          <input
            class="cw-login__input"
            name="password"
            id="login_password"
            type="password"
            placeholder="<?= lang('placeholder_password'); ?>"
            autocomplete="current-password"
            required>
        </div>
      </div>

      <!-- reCaptcha -->
      <?php if ($this->wowmodule->getreCaptchaStatus() == '1'): ?>
        <div class="cw-login__captcha">
          <div class="g-recaptcha" data-sitekey="<?= $recapKey; ?>"></div>
        </div>
      <?php endif; ?>

      <!-- Actions -->
      <div class="cw-login__actions">
        <button class="cw-login__btn" id="button_login" type="submit">
          <i class="fas fa-sign-in-alt"></i><?= lang('button_login'); ?>
        </button>
        <a href="<?= base_url('recovery'); ?>" class="cw-login__forgot">
          <i class="fas fa-key"></i><?= lang('button_forgot_password'); ?>
        </a>
      </div>

      <?= form_close(); ?>

      <!-- Lien inscription -->
      <?php if ($this->wowmodule->getRegisterStatus() == '1'): ?>
        <div class="cw-login__sep"></div>
        <p class="cw-login__register-hint">
          Pas encore de compte ?
        </p>
        <p class="cw-login__register-hint">
          <a href="<?= base_url('register'); ?>">
            <i class="fas fa-user-plus" style="font-size:1rem;margin-right:.2rem"></i>S'inscrire
          </a>
        </p>
      <?php endif; ?>

    </div><!-- /card -->
  </div><!-- /panel -->

</div><!-- /cw-login -->


<script>
  (function() {
    /* ── Particules flottantes ── */
    var container = document.getElementById('cwParticles');
    if (!container) return;

    var count = 28;
    for (var i = 0; i < count; i++) {
      var p = document.createElement('span');
      p.className = 'cw-particle';

      var size = Math.random() * 2.5 + .5;
      var left = Math.random() * 100;
      var delay = Math.random() * 12;
      var dur = Math.random() * 10 + 10;
      var blur = Math.random() < .4 ? '0 0 4px rgba(180,200,220,.6)' : 'none';

      p.style.cssText = [
        'width:' + size + 'px',
        'height:' + size + 'px',
        'left:' + left + '%',
        'bottom:' + (Math.random() * -10) + '%',
        'box-shadow:' + blur,
        'animation-duration:' + dur + 's',
        'animation-delay:-' + delay + 's',
      ].join(';');

      container.appendChild(p);
    }
  })();
</script>