<?php
/* ═══════════════════════════════════════════════════════════════════════
   register.php — Vue Celestia-WoW
   NB: redirect() impossible ici (headers déjà envoyés par le controller).
   Les redirections se font via JS window.location côté client.
═══════════════════════════════════════════════════════════════════════ */

$_CI   =& get_instance();

/* ── Récupération du realm (protection contre requête échouée) ── */
$_realms_query = $_CI->wowrealm->getRealms();
$realm = ($_realms_query && $_realms_query->num_rows() > 0)
    ? $_realms_query->row()
    : null;

if ( ! $realm) {
    log_message('error', 'register.php : getRealms() n\'a retourné aucun résultat.');
    show_error('Aucun realm configuré. Veuillez contacter un administrateur.', 503);
    exit;
}

$webDB  = $_CI->wowrealm->realmConnection(
    $realm->username, $realm->password, $realm->hostname,
    isset($realm->web_database)  ? $realm->web_database  : 'R0_Website'
);
$authDB = $_CI->wowrealm->realmConnection(
    $realm->username, $realm->password, $realm->hostname,
    isset($realm->auth_database) ? $realm->auth_database : 'R0_Auth'
);

/* ── Flag promo NB856CW via R1_Eluna ── */
try {
    $elunaDB      = $_CI->wowrealm->realmConnection(
        $realm->username, $realm->password, $realm->hostname, 'R1_Eluna'
    );
    $_promo_query = $elunaDB->where('code', 'NB856CW')->get('player_code');
    $promo_row    = ($_promo_query && $_promo_query->num_rows() > 0)
        ? $_promo_query->row()
        : null;
    $promo_active = ($promo_row && (int)$promo_row->enabled === 1);
} catch (Exception $e) {
    $promo_active = false;
}

/* ── Config parrainage : génération vs affichage séparés ── */
$_ref_query     = $webDB ? $webDB->get('referral_config') : null;
$ref_cfg_row    = ($_ref_query && $_ref_query->num_rows() > 0)
    ? $_ref_query->row()
    : null;
$ref_gen_active = (int)($ref_cfg_row->active ?? 0);

/* ── Variables de résultat ── */
$confirm_result   = ''; /* 'autologin' | 'expired' | 'invalid' */
$confirm_msg      = '';
$confirm_msg_type = '';
$reg_msg          = '';
$reg_type         = '';
$new_email        = '';

/* Erreurs de confirmation via flashdata (depuis Activate.php) */

/* ── Flashdata succès inscription ── */
$reg_success_email = $_CI->session->flashdata('reg_success_email');
if ($reg_success_email) {
    $reg_msg   = 'Compte créé ! Un email de confirmation a été envoyé à <strong>'
               . htmlspecialchars($reg_success_email)
               . '</strong>. Cliquez sur le lien pour activer votre compte <em>(valable 24h)</em>.';
    $reg_type  = 'success';
    $new_email = $reg_success_email;
}
?>



<style>
/* ═══════════════════════════════════════════════════════════
   REGISTER PAGE — même charte que login.php
═══════════════════════════════════════════════════════════ */
.cw-reg {
  --silver: #c8d4e0;
  --silver-soft: #e2eaf2;
  --silver-dim: #8a9fb5;
  --silver-pale: rgba(180,200,220,.07);
  --silver-glow: rgba(180,200,220,.15);
  --silver-line: rgba(180,200,220,.18);
  --bg-deep: #020910;
  --bg-panel: rgba(6,17,28,.92);
  --bg-input: rgba(8,22,36,.85);
  --text-hi: #d8e4f0;
  --text-mid: #8da5bb;
  --text-lo: #4a6278;
  --border: rgba(180,200,220,.18);
  --border-lo: rgba(180,200,220,.09);
  --gold: #c8a84b;
  --gold-soft: #e0c060;
  --gold-glow: rgba(200,168,75,.25);
  --danger: #e05555;
  --success: #5dbe7a;
  --font-rune: 'Cinzel', Georgia, serif;
  --font-prose: 'Crimson Pro', Georgia, serif;
  --t: .3s;
  --ease-out: cubic-bezier(.22,1,.36,1);
}

.cw-reg *, .cw-reg *::before, .cw-reg *::after {
  box-sizing: border-box; margin: 0; padding: 0;
}

/* ── Layout split ── */
.cw-reg {
  position: relative;
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--bg-deep);
  overflow: hidden;
}

/* Texture bruit */
.cw-reg__noise {
  position: absolute; inset: 0; z-index: 1;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
  pointer-events: none; opacity: .5;
}

/* ════ GAUCHE — identité ════ */
.cw-reg__identity {
  position: relative; z-index: 2;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: calc(var(--nav-h,86px) + 2.5rem) 3rem 4rem;
  gap: 2.5rem;
  border-right: 1px solid var(--border-lo);
}

/* Lueurs ambiantes gauche */
.cw-reg__identity::before {
  content: '';
  position: absolute; inset: 0; z-index: -1;
  background:
    radial-gradient(ellipse 60% 55% at 30% 40%, rgba(180,200,220,.04) 0%, transparent 70%),
    radial-gradient(ellipse 40% 30% at 70% 80%, rgba(200,168,75,.025) 0%, transparent 60%);
}

.cw-reg__logo-link img {
  max-width: 220px; height: auto;
  filter: drop-shadow(0 0 24px rgba(180,200,220,.18));
  transition: filter var(--t);
}
.cw-reg__logo-link:hover img {
  filter: drop-shadow(0 0 36px rgba(180,200,220,.35));
}

.cw-reg__brand { text-align: center; }
.cw-reg__brand-name {
  display: block;
  font-family: var(--font-rune);
  font-size: 1.5rem; letter-spacing: .22em;
  text-transform: uppercase; color: var(--silver);
}
.cw-reg__brand-sub {
  display: block; margin-top: .3rem;
  font-family: var(--font-prose); font-style: italic;
  font-size: 1rem; color: var(--text-lo);
}

.cw-reg__ornament {
  text-align: center;
}
.cw-reg__ornament-glyph {
  font-family: var(--font-rune);
  font-size: .9rem; letter-spacing: .6em;
  color: var(--silver-dim); opacity: .5;
}

.cw-reg__tagline {
  font-family: var(--font-prose);
  font-size: 1.1rem; font-style: italic;
  color: var(--text-lo); text-align: center;
  line-height: 1.8;
}

/* ════ DROITE — formulaire ════ */
.cw-reg__panel {
  position: relative; z-index: 2;
  display: flex; align-items: flex-start; justify-content: center;
  padding: calc(var(--nav-h,86px) + 1.5rem) 2.5rem 3rem;
  overflow-y: auto;
}

.cw-reg__card {
  width: 100%; max-width: 440px;
  background: var(--bg-panel);
  border: 1px solid var(--border-lo);
  border-radius: 14px;
  padding: 2rem 2.25rem 2rem;
  backdrop-filter: blur(16px);
  box-shadow: 0 8px 48px rgba(0,0,0,.55), 0 1px 0 rgba(180,200,220,.06) inset;
  position: relative; overflow: visible;
}

/* Liseré top */
.cw-reg__card::before {
  content: '';
  position: absolute; top: -1px; left: 8%; right: 8%; height: 1px;
  background: linear-gradient(90deg,
    transparent 0%, rgba(180,200,220,.25) 30%,
    rgba(180,200,220,.5) 50%, rgba(180,200,220,.25) 70%, transparent 100%);
  border-radius: 1px;
}

/* ── En-tête carte ── */
.cw-reg__card-head { margin-bottom: 1.5rem; }
.cw-reg__card-title {
  font-family: var(--font-rune);
  font-size: 1.2rem; letter-spacing: .2em;
  text-transform: uppercase; color: var(--silver);
  display: flex; align-items: center; gap: .6rem;
}
.cw-reg__card-title i { color: var(--silver-dim); font-size: 1rem; }
.cw-reg__card-sub {
  display: block; margin-top: .3rem;
  font-family: var(--font-prose); font-style: italic;
  font-size: .95rem; color: var(--text-lo);
}

/* ════ ENCART CODE PROMO ════ */
.cw-reg__promo {
  position: relative; overflow: hidden;
  background: linear-gradient(135deg, rgba(200,168,75,.08) 0%, rgba(200,168,75,.04) 100%);
  border: 1px solid rgba(200,168,75,.3);
  border-radius: 10px;
  padding: .9rem 1.1rem;
  margin-bottom: 1.5rem;
  display: flex; align-items: flex-start; gap: .85rem;
}

/* Liseré doré top */
.cw-reg__promo::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
}

/* Rayon lumineux animé */
.cw-reg__promo-ray {
  position: absolute; top: 0; left: -80%;
  width: 60%; height: 100%;
  background: linear-gradient(105deg,
    transparent 30%, rgba(255,230,120,.07) 50%, transparent 70%);
  transform: skewX(-15deg);
  animation: cwRegRaySweep 3.5s ease-in-out infinite;
  pointer-events: none;
}
@keyframes cwRegRaySweep {
  0%   { left: -80%; opacity: 0; }
  10%  { opacity: 1; }
  90%  { opacity: 1; }
  100% { left: 140%; opacity: 0; }
}

.cw-reg__promo-icon {
  flex-shrink: 0; margin-top: .1rem;
  font-size: 1.3rem; color: var(--gold);
  filter: drop-shadow(0 0 8px rgba(200,168,75,.5));
}

.cw-reg__promo-body { flex: 1; min-width: 0; }

.cw-reg__promo-badge {
  display: inline-block; margin-bottom: .3rem;
  font-family: var(--font-rune); font-size: .72rem;
  letter-spacing: .18em; text-transform: uppercase;
  color: rgba(200,168,75,.7);
  border: 1px solid rgba(200,168,75,.2);
  border-radius: 4px; padding: .1rem .45rem;
}

.cw-reg__promo-title {
  font-family: var(--font-rune);
  font-size: 1rem; letter-spacing: .08em; color: var(--gold-soft);
  display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
}

.cw-reg__promo-code {
  font-family: 'Courier New', Courier, monospace;
  font-size: 1.05rem; letter-spacing: .3em; font-weight: bold;
  color: #f0d878;
  background: rgba(200,168,75,.12);
  border: 1px solid rgba(200,168,75,.25);
  border-radius: 5px; padding: .1rem .5rem;
  cursor: pointer; transition: background var(--t), box-shadow var(--t);
  white-space: nowrap;
}
.cw-reg__promo-code:hover {
  background: rgba(200,168,75,.22);
  box-shadow: 0 0 10px rgba(200,168,75,.2);
}

.cw-reg__promo-desc {
  margin-top: .3rem;
  font-family: var(--font-prose); font-style: italic;
  font-size: .95rem; color: var(--text-mid); line-height: 1.4;
}
.cw-reg__promo-desc strong { color: var(--silver-dim); font-style: normal; }

/* Toast copie */
.cw-reg__toast {
  position: fixed; bottom: 2rem; left: 50%;
  transform: translateX(-50%);
  background: rgba(10,26,44,.97);
  border: 1px solid rgba(200,168,75,.3);
  border-radius: 8px; padding: .6rem 1.2rem;
  font-family: var(--font-rune); font-size: .9rem;
  letter-spacing: .1em; color: var(--gold);
  pointer-events: none; opacity: 0; z-index: 9999;
  transition: opacity .25s;
}
.cw-reg__toast.show { opacity: 1; }

/* ── Alertes ── */
.cw-reg__alert {
  display: flex; align-items: flex-start; gap: .6rem;
  padding: .75rem 1rem; border-radius: 8px;
  margin-bottom: 1.25rem;
  font-family: var(--font-prose); font-size: 1rem; line-height: 1.5;
}
.cw-reg__alert i { margin-top: .1rem; flex-shrink: 0; }
.cw-reg__alert--danger {
  background: rgba(220,80,80,.08); border: 1px solid rgba(220,80,80,.2); color: #e07070;
}
.cw-reg__alert--success {
  background: rgba(93,190,122,.08); border: 1px solid rgba(93,190,122,.2); color: #6dce8a;
}

/* ── Sections du formulaire ── */
.cw-reg__section {
  margin-bottom: .25rem;
}
.cw-reg__section-label {
  display: block; margin-bottom: .6rem;
  font-family: var(--font-rune); font-size: .8rem;
  letter-spacing: .18em; text-transform: uppercase;
  color: var(--text-lo);
}

/* ── Champs ── */
.cw-reg__field { margin-bottom: .75rem; }
.cw-reg__field-wrap {
  position: relative; display: flex; align-items: center;
}
.cw-reg__field-icon {
  position: absolute; left: 1rem;
  color: var(--silver-dim); font-size: 1rem;
  pointer-events: none; transition: color var(--t); z-index: 1;
}
.cw-reg__input {
  width: 100%; padding: .78rem 1rem .78rem 2.8rem;
  background: var(--bg-input);
  border: 1px solid var(--border-lo);
  border-radius: 8px;
  font-family: var(--font-prose); font-size: 1rem;
  color: var(--text-hi); letter-spacing: .04em;
  outline: none;
  transition: border-color var(--t), box-shadow var(--t), background var(--t);
  -webkit-appearance: none;
}
.cw-reg__input::placeholder { color: var(--text-lo); }
.cw-reg__input:focus {
  border-color: var(--silver-dim);
  background: rgba(8,22,36,.95);
  box-shadow: 0 0 0 3px rgba(180,200,220,.08), inset 0 1px 3px rgba(0,0,0,.3);
}
.cw-reg__field-wrap:focus-within .cw-reg__field-icon { color: var(--silver); }

/* Champ token parrainage — accent doré */
.cw-reg__field--referral .cw-reg__input {
  border-color: rgba(200,168,75,.15);
  letter-spacing: .18em;
  font-family: 'Courier New', Courier, monospace;
  text-transform: uppercase;
}
.cw-reg__field--referral .cw-reg__input:focus {
  border-color: rgba(200,168,75,.4);
  box-shadow: 0 0 0 3px rgba(200,168,75,.07), inset 0 1px 3px rgba(0,0,0,.3);
}
.cw-reg__field--referral .cw-reg__field-icon { color: rgba(200,168,75,.5); }
.cw-reg__field-wrap:focus-within .cw-reg__field--referral .cw-reg__field-icon,
.cw-reg__field--referral .cw-reg__field-wrap:focus-within .cw-reg__field-icon {
  color: var(--gold);
}

/* Select pays */
.cw-reg__select {
  width: 100%; padding: .78rem 1rem .78rem 2.8rem;
  background: var(--bg-input);
  border: 1px solid var(--border-lo);
  border-radius: 8px;
  font-family: var(--font-prose); font-size: 1rem;
  color: var(--text-hi);
  outline: none; cursor: pointer;
  transition: border-color var(--t), box-shadow var(--t);
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%238a9fb5' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: calc(100% - 1rem) center;
  padding-right: 2.5rem;
}
.cw-reg__select:focus {
  border-color: var(--silver-dim);
  box-shadow: 0 0 0 3px rgba(180,200,220,.08);
}
.cw-reg__select option { background: #0a1a2c; color: var(--text-hi); }

/* ── Séparateur ── */
.cw-reg__sep {
  height: 1px; margin: 1rem 0;
  background: linear-gradient(90deg, transparent, var(--border-lo), transparent);
}

/* ── CGU ── */
.cw-reg__cgu {
  display: flex; align-items: flex-start; gap: .7rem;
  margin-bottom: 1.25rem; cursor: pointer;
}
.cw-reg__cgu input[type="checkbox"] {
  flex-shrink: 0; width: 16px; height: 16px;
  margin-top: .2rem; accent-color: var(--silver-dim);
  cursor: pointer;
}
.cw-reg__cgu-text {
  font-family: var(--font-prose); font-size: .92rem;
  color: var(--text-lo); line-height: 1.5;
}
.cw-reg__cgu-text a {
  color: var(--silver-dim); text-decoration: underline;
  text-underline-offset: 3px; transition: color var(--t);
}
.cw-reg__cgu-text a:hover { color: var(--silver); }

/* ── Hint champ ── */
.cw-reg__field-hint {
  margin-top: .35rem; padding-left: .2rem;
  font-family: var(--font-prose); font-size: 1rem;
  color: var(--text-lo); line-height: 1.4;
  transition: color var(--t);
}
.cw-reg__field-hint i { font-size: .85rem; margin-right: .25rem; }
.cw-reg__field-hint.ok   { color: var(--success, #5dbe7a); }
.cw-reg__field-hint.warn { color: var(--danger,  #e05555); }

/* ── reCaptcha ── */
.cw-reg__captcha { margin-bottom: 1.25rem; }

/* ── Bouton submit ── */
.cw-reg__btn {
  display: inline-flex; align-items: center; justify-content: center;
  gap: .5rem; padding: .9rem 2rem; width: 100%;
  font-family: var(--font-rune); font-size: 1rem;
  letter-spacing: .18em; text-transform: uppercase;
  background: transparent; color: var(--silver-soft);
  border: 1px solid var(--silver-dim);
  border-radius: 8px; cursor: pointer;
  transition: background var(--t), border-color var(--t), box-shadow var(--t);
  position: relative; overflow: hidden; white-space: nowrap;
}
.cw-reg__btn::before {
  content: '';
  position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.06) 50%, transparent 100%);
  transition: left .5s var(--ease-out);
}
.cw-reg__btn:hover::before { left: 100%; }
.cw-reg__btn:hover {
  background: rgba(180,200,220,.1);
  border-color: var(--silver);
  box-shadow: 0 0 20px rgba(180,200,220,.12);
}
.cw-reg__btn:active { transform: scale(.98); }

/* ── Lien connexion ── */
.cw-reg__login-hint {
  margin-top: 1.5rem; text-align: center;
  font-family: var(--font-prose); font-style: italic;
  font-size: .95rem; color: var(--text-lo);
}
.cw-reg__login-hint a {
  color: var(--silver-dim); text-decoration: none;
  font-style: normal; font-family: var(--font-rune);
  letter-spacing: .08em; transition: color var(--t);
}
.cw-reg__login-hint a:hover { color: var(--silver); }

/* ── Séparateur de section ── */
.cw-reg__section-sep {
  display: flex; align-items: center; gap: .75rem;
  margin: 1rem 0 .75rem;
}
.cw-reg__section-sep span {
  flex: 1; height: 1px;
  background: linear-gradient(90deg, transparent, var(--border-lo), transparent);
}
.cw-reg__section-sep em {
  font-family: var(--font-rune); font-style: normal;
  font-size: .72rem; letter-spacing: .18em; text-transform: uppercase;
  color: var(--text-lo);
}

/* ── Particules ── */
.cw-reg__particles {
  position: absolute; inset: 0; z-index: 1;
  pointer-events: none; overflow: hidden;
}
.cw-rp {
  position: absolute; border-radius: 50%;
  background: rgba(180,200,220,.55);
  animation: cwRegFloat linear infinite;
}
@keyframes cwRegFloat {
  from { transform: translateY(0) rotate(0deg); opacity: 0; }
  10%  { opacity: 1; }
  90%  { opacity: .4; }
  to   { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
}

/* ── Responsive ── */
@media (max-width: 800px) {
  .cw-reg { grid-template-columns: 1fr; }
  .cw-reg__identity { display: none; }
  .cw-reg__panel { padding: calc(var(--nav-h,62px) + 1rem) 1.25rem 2.5rem; }
}

/* ── Popup confirmation email ── */
.cw-reg__popup-overlay {
  position: fixed; inset: 0; z-index: 9000;
  background: rgba(2,9,16,.85);
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(6px);
  animation: cwPopFadeIn .35s var(--ease-out);
}
@keyframes cwPopFadeIn { from{opacity:0} to{opacity:1} }
.cw-reg__popup {
  position: relative;
  max-width: 480px; width: 92%;
  background: rgba(8,20,34,.97);
  border: 1px solid rgba(180,200,220,.2);
  border-radius: 16px;
  padding: 2.5rem 2rem 2rem;
  text-align: center;
  box-shadow: 0 24px 80px rgba(0,0,0,.8), 0 0 0 1px rgba(180,200,220,.06) inset;
  animation: cwPopSlideUp .4s var(--ease-out);
}
@keyframes cwPopSlideUp { from{transform:translateY(32px);opacity:0} to{transform:translateY(0);opacity:1} }
.cw-reg__popup::before {
  content: '';
  position: absolute; top: -1px; left: 10%; right: 10%; height: 2px;
  background: linear-gradient(90deg, transparent, rgba(93,190,122,.6), rgba(93,190,122,.9), rgba(93,190,122,.6), transparent);
  border-radius: 2px;
}
.cw-reg__popup-icon {
  font-size: 3rem; margin-bottom: 1rem;
  filter: drop-shadow(0 0 20px rgba(93,190,122,.5));
  animation: cwPopIconPulse 2s ease-in-out infinite;
}
@keyframes cwPopIconPulse { 0%,100%{filter:drop-shadow(0 0 16px rgba(93,190,122,.4))} 50%{filter:drop-shadow(0 0 30px rgba(93,190,122,.8))} }
.cw-reg__popup-title {
  font-family: var(--font-rune);
  font-size: 1.3rem; letter-spacing: .15em; text-transform: uppercase;
  color: var(--silver); margin-bottom: .75rem;
}
.cw-reg__popup-email {
  display: inline-block;
  font-family: 'Courier New', monospace; font-size: 1.1rem;
  color: #6dce8a; letter-spacing: .05em;
  background: rgba(93,190,122,.08); border: 1px solid rgba(93,190,122,.2);
  border-radius: 6px; padding: .3rem .8rem; margin: .5rem 0 1rem;
}
.cw-reg__popup-body {
  font-family: var(--font-prose); font-size: 1.15rem;
  color: var(--text-mid); line-height: 1.7; margin-bottom: 1.25rem;
}
.cw-reg__popup-steps {
  text-align: left;
  background: rgba(180,200,220,.04); border: 1px solid rgba(180,200,220,.1);
  border-radius: 8px; padding: .9rem 1.1rem; margin-bottom: 1.5rem;
  font-family: var(--font-prose); font-size: 1.1rem; color: var(--text-mid);
  line-height: 1.9;
}
.cw-reg__popup-steps span { color: var(--silver-dim); font-weight: bold; }
.cw-reg__popup-close {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .75rem 2rem;
  font-family: var(--font-rune); font-size: 1.1rem;
  letter-spacing: .15em; text-transform: uppercase;
  background: transparent; color: var(--silver);
  border: 1px solid var(--silver-dim); border-radius: 8px;
  cursor: pointer; transition: background var(--t), box-shadow var(--t);
}
.cw-reg__popup-close:hover {
  background: rgba(180,200,220,.1); box-shadow: 0 0 18px rgba(180,200,220,.1);
}
</style>

<div class="cw-reg">
<div id="cwRegPopup" class="cw-reg__popup-overlay" style="display:none">
  <div class="cw-reg__popup">
    <div class="cw-reg__popup-icon">&#9993;</div>
    <div class="cw-reg__popup-title">Email envoyé !</div>
    <div class="cw-reg__popup-email" id="cwPopEmail"></div>
    <div class="cw-reg__popup-body">
      Un email de confirmation vous a été envoyé.<br>
      Votre compte est créé mais <strong style="color:var(--silver)">bloqué</strong> tant que vous n&rsquo;avez pas validé votre adresse.
    </div>
    <div class="cw-reg__popup-steps">
      <span>1.</span> Ouvrez votre boîte mail<br>
      <span>2.</span> Cliquez sur le lien &laquo;&nbsp;Activer mon compte&nbsp;&raquo;<br>
      <span>3.</span> Revenez vous connecter
    </div>
    <button class="cw-reg__popup-close" id="cwPopCloseBtn" data-href="<?= base_url(); ?>">
      <i class="fas fa-check"></i> Compris
    </button>
  </div>
</div>

  <div class="cw-reg__noise"></div>
  <div class="cw-reg__particles" id="cwRegParticles"></div>

  <!-- ═══ GAUCHE — Logo + identité ═══ -->
  <div class="cw-reg__identity">
    <a href="<?= base_url(); ?>" class="cw-reg__logo-link">
      <img
        src="<?= $template['location'] . 'assets/images/logo-celestien.png'; ?>"
        alt="<?= $this->config->item('website_name'); ?>">
    </a>
    <div class="cw-reg__brand">
      <span class="cw-reg__brand-name"><?= $this->config->item('website_name'); ?></span>
      <span class="cw-reg__brand-sub">Serveur privé World of Warcraft</span>
    </div>
    <div class="cw-reg__ornament">
      <span class="cw-reg__ornament-glyph">✦ ✦ ✦</span>
    </div>
    <p class="cw-reg__tagline">
      Rejoignez l'aventure.<br>Votre légende commence ici.
    </p>
  </div>

  <!-- ═══ DROITE — Formulaire ═══ -->
  <div class="cw-reg__panel">
    <div class="cw-reg__card">

      <!-- En-tête -->
      <div class="cw-reg__card-head">
        <h1 class="cw-reg__card-title">
          <i class="fas fa-user-plus"></i>
          <?= lang('button_register'); ?>
        </h1>
        <span class="cw-reg__card-sub">Créez votre compte gratuitement</span>
      </div>

      <!-- ══ CODE PROMO NOUVEAUX JOUEURS ══ -->
      <?php if ($promo_active): ?>
      <div class="cw-reg__promo">
        <div class="cw-reg__promo-ray"></div>
        <div class="cw-reg__promo-icon">
          <i class="fas fa-gift"></i>
        </div>
        <div class="cw-reg__promo-body">
          <span class="cw-reg__promo-badge">&#10024; Réservé aux nouveaux joueurs</span>
          <div class="cw-reg__promo-title">
            Cadeau de bienvenue&nbsp;:
            <span
              class="cw-reg__promo-code"
              id="cwPromoCode"
              onclick="cwRegCopyPromo()"
              title="Cliquer pour copier">NB856CW</span>
          </div>
          <p class="cw-reg__promo-desc">
            1 Sésame 80 offert &mdash; à activer en jeu auprès de
            <strong>Tyraël</strong> à l'aide de ce code.
          </p>
        </div>
      </div>
      <?php endif; ?>

      <!-- Alertes confirmation email -->
      <?php if (!empty($confirm_msg)): ?>
        <div class="cw-reg__alert cw-reg__alert--<?= $confirm_msg_type === 'success' ? 'success' : 'danger'; ?>">
          <i class="far fa-<?= $confirm_msg_type === 'success' ? 'circle-check' : 'circle-xmark'; ?>"></i>
          <span><?= $confirm_msg; ?></span>
        </div>
      <?php endif; ?>

      <!-- Alertes inscription -->
      <?php if (!empty($reg_msg)): ?>
        <?php if ($reg_type === 'success'): ?>
        <div id="cwRegSuccessData"
             data-email="<?= htmlspecialchars($new_email); ?>"
             style="display:none"></div>
        <?php endif; ?>
        <div class="cw-reg__alert cw-reg__alert--<?= $reg_type === 'success' ? 'success' : 'danger'; ?>">
          <i class="far fa-<?= $reg_type === 'success' ? 'circle-check' : 'circle-xmark'; ?>"></i>
          <span><?= $reg_msg; ?></span>
        </div>
      <?php endif; ?>

      <!-- Alertes BlizzCMS natives -->
      <?php echo validation_errors('<div class="cw-reg__alert cw-reg__alert--danger"><i class="far fa-circle-xmark"></i><span>', '</span></div>'); ?>
      <?php if (isset($msg_notification_account_already_exist)): ?>
        <div class="cw-reg__alert cw-reg__alert--danger">
          <i class="far fa-circle-xmark"></i>
          <span><?= $msg_notification_account_already_exist; ?></span>
        </div>
      <?php endif; ?>
      <?php if (isset($msg_notification_used_email)): ?>
        <div class="cw-reg__alert cw-reg__alert--danger">
          <i class="far fa-circle-xmark"></i>
          <span><?= $msg_notification_used_email; ?></span>
        </div>
      <?php endif; ?>
      <?php if (isset($msg_referral_invalid)): ?>
        <div class="cw-reg__alert cw-reg__alert--danger">
          <i class="far fa-circle-xmark"></i>
          <span><?= $msg_referral_invalid; ?></span>
        </div>
      <?php endif; ?>


      <!-- ══ FORMULAIRE ══ -->
      <?= form_open(current_url()); ?>

      <!-- Section : Compte -->
      <div class="cw-reg__section">
        <span class="cw-reg__section-label"><i class="fas fa-user" style="margin-right:.4rem"></i>Informations de compte</span>

        <!-- Nom d'utilisateur -->
        <div class="cw-reg__field">
          <div class="cw-reg__field-wrap">
            <i class="fas fa-user cw-reg__field-icon"></i>
            <input
              class="cw-reg__input"
              type="text" name="username" id="reg_username"
              pattern=".{3,16}" minlength="3" maxlength="16"
              placeholder="<?= lang('placeholder_username'); ?>"
              autocomplete="username"
              value="<?= set_value('username'); ?>"
              required>
          </div>
        </div>

        <!-- Email -->
        <div class="cw-reg__field">
          <div class="cw-reg__field-wrap">
            <i class="fas fa-envelope cw-reg__field-icon"></i>
            <input
              class="cw-reg__input"
              type="email" name="email" id="reg_email"
              placeholder="<?= lang('placeholder_email'); ?>"
              autocomplete="email"
              value="<?= set_value('email'); ?>"
              required>
          </div>
        </div>

        <!-- Mot de passe -->
        <div class="cw-reg__field">
          <div class="cw-reg__field-wrap">
            <i class="fas fa-lock cw-reg__field-icon"></i>
            <input
              class="cw-reg__input"
              type="password" name="password" id="reg_password"
              pattern=".{8,16}" minlength="8" maxlength="16"
              placeholder="<?= lang('placeholder_password'); ?>"
              autocomplete="new-password"
              required>
          </div>
          <div class="cw-reg__field-hint" id="pwHint">
            <i class="fas fa-circle-info"></i> 8 caract&egrave;res minimum &mdash; 16 maximum
          </div>
        </div>

        <!-- Confirmation mot de passe -->
        <div class="cw-reg__field">
          <div class="cw-reg__field-wrap">
            <i class="fas fa-shield-halved cw-reg__field-icon"></i>
            <input
              class="cw-reg__input"
              type="password" name="confirm_password" id="reg_repassword"
              pattern=".{8,16}" minlength="8" maxlength="16"
              placeholder="<?= lang('placeholder_re_password'); ?>"
              autocomplete="new-password"
              required>
          </div>
        </div>
      </div>

      <!-- Section : Pays -->
      <div class="cw-reg__section-sep"><span></span><em>Localisation</em><span></span></div>
      <div class="cw-reg__field">
        <div class="cw-reg__field-wrap">
          <i class="fas fa-globe cw-reg__field-icon"></i>
          <select class="cw-reg__select" name="country" id="reg_country">
            <option value="" disabled selected>Votre pays / région…</option>
            <option value="FR">🇫🇷 France</option>
            <option value="BE">🇧🇪 Belgique</option>
            <option value="CH">🇨🇭 Suisse</option>
            <option value="CA">🇨🇦 Canada</option>
            <option value="LU">🇱🇺 Luxembourg</option>
            <option value="MA">🇲🇦 Maroc</option>
            <option value="DZ">🇩🇿 Algérie</option>
            <option value="TN">🇹🇳 Tunisie</option>
            <option value="OTHER">🌍 Autre</option>
          </select>
        </div>
      </div>

      <!-- Section : Parrainage (toujours visible) -->
      <div class="cw-reg__section-sep"><span></span><em>Parrainage</em><span></span></div>
      <div class="cw-reg__field cw-reg__field--referral">
        <div class="cw-reg__field-wrap">
          <i class="fas fa-key cw-reg__field-icon"></i>
          <input
            class="cw-reg__input"
            type="text" name="referral_token" id="reg_referral"
            maxlength="8" pattern="[A-Za-z0-9]{8}"
            placeholder="Code de parrainage (optionnel)"
            autocomplete="off"
            value="<?= set_value('referral_token'); ?>">
        </div>
      </div>

      <div class="cw-reg__sep"></div>

      <!-- CGU -->
      <label class="cw-reg__cgu">
        <input type="checkbox" name="cgu" id="reg_cgu" required>
        <span class="cw-reg__cgu-text">
          J'ai lu et j'accepte les
          <a href="<?= base_url('terms'); ?>" target="_blank">Conditions Générales d'Utilisation</a>
          et la <a href="<?= base_url('privacy'); ?>" target="_blank">Politique de confidentialité</a>
          de <?= $this->config->item('website_name'); ?>.
        </span>
      </label>

      <!-- reCaptcha -->
      <?php if ($this->wowmodule->getreCaptchaStatus() == '1'): ?>
        <div class="cw-reg__captcha">
          <div class="g-recaptcha" data-sitekey="<?= $recapKey; ?>"></div>
        </div>
      <?php endif; ?>

      <!-- Bouton -->
      <button class="cw-reg__btn" id="button_register" type="submit">
        <i class="fas fa-user-plus"></i><?= lang('button_register'); ?>
      </button>

      <?= form_close(); ?>

      <!-- Lien connexion -->
      <div class="cw-reg__login-hint">
        Déjà un compte ?
      </div>
      <div class="cw-reg__login-hint" style="margin-top:.4rem">
        <a href="<?= base_url('login'); ?>">
          <i class="fas fa-sign-in-alt" style="font-size:.9rem;margin-right:.3rem"></i>Se connecter
        </a>
      </div>

    </div><!-- /card -->
  </div><!-- /panel -->

</div><!-- /cw-reg -->

<!-- Toast copie code promo -->
<div class="cw-reg__toast" id="cwRegToast">&#10003; Code copié !</div>

<script>
(function () {
  /* ── Particules ambiantes ── */
  var cont = document.getElementById('cwRegParticles');
  if (cont) {
    for (var i = 0; i < 28; i++) {
      var p = document.createElement('span');
      p.className = 'cw-rp';
      var sz = Math.random() * 2.5 + .5;
      var left = Math.random() * 100;
      var delay = Math.random() * 14;
      var dur = Math.random() * 10 + 12;
      p.style.cssText = [
        'width:' + sz + 'px',
        'height:' + sz + 'px',
        'left:' + left + '%',
        'bottom:' + (Math.random() * -10) + '%',
        'animation-duration:' + dur + 's',
        'animation-delay:-' + delay + 's',
        'opacity:0'
      ].join(';');
      cont.appendChild(p);
    }
  }

  /* ── Validation temps réel mot de passe ── */
  var pass   = document.getElementById('reg_password');
  var rep    = document.getElementById('reg_repassword');
  var pwHint = document.getElementById('pwHint');

  if (pass && pwHint) {
    pass.addEventListener('input', function () {
      var len = pass.value.length;
      if (len === 0) {
        pwHint.className = 'cw-reg__field-hint';
        pwHint.innerHTML = '<i class="fas fa-circle-info"></i> 8 caract&egrave;res minimum &mdash; 16 maximum';
      } else if (len < 8) {
        pwHint.className = 'cw-reg__field-hint warn';
        pwHint.innerHTML = '<i class="fas fa-triangle-exclamation"></i> ' + len + '&thinsp;/&thinsp;8 caract&egrave;res minimum';
      } else {
        pwHint.className = 'cw-reg__field-hint ok';
        pwHint.innerHTML = '<i class="fas fa-circle-check"></i> Longueur valide (' + len + ' caract&egrave;res)';
      }
    });
  }

  if (pass && rep) {
    rep.addEventListener('input', function () {
      if (rep.value && rep.value !== pass.value) {
        rep.style.borderColor = 'rgba(220,80,80,.5)';
      } else {
        rep.style.borderColor = '';
      }
    });
  }

  /* ── Token parrainage : uppercase auto ── */
  var refInput = document.getElementById('reg_referral');
  if (refInput) {
    refInput.addEventListener('input', function () {
      var pos = this.selectionStart;
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
      this.setSelectionRange(pos, pos);
    });
  }
})();

/* ── Popup confirmation email ── */
(function () {
  var dataEl  = document.getElementById('cwRegSuccessData');
  var closeBtn = document.getElementById('cwPopCloseBtn');
  if (dataEl) {
    var popup   = document.getElementById('cwRegPopup');
    var emailEl = document.getElementById('cwPopEmail');
    if (popup) {
      if (emailEl) emailEl.textContent = dataEl.getAttribute('data-email') || '';
      popup.style.display = 'flex';
      /* Fermer overlay → accueil */
      popup.addEventListener('click', function (e) {
        if (e.target === popup) {
          window.location.href = (closeBtn && closeBtn.getAttribute('data-href')) || '/';
        }
      });
    }
  }
  /* Bouton Compris → accueil */
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      window.location.href = closeBtn.getAttribute('data-href') || '/';
    });
  }
})();

/* ── Copie code promo ── */
function cwRegCopyPromo() {
  var code = document.getElementById('cwPromoCode');
  var toast = document.getElementById('cwRegToast');
  if (!code || !toast) return;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(code.textContent.trim());
  } else {
    var ta = document.createElement('textarea');
    ta.value = code.textContent.trim();
    document.body.appendChild(ta);
    ta.select(); document.execCommand('copy');
    document.body.removeChild(ta);
  }
  toast.classList.add('show');
  setTimeout(function () { toast.classList.remove('show'); }, 2200);
}
</script>
