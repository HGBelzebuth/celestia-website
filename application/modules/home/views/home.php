<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">

<style>
  /* ═══════════════════════════════════════════════════════════
   VARIABLES 2026 — THÈME HYBRIDE : GIVRE (WOTLK) x SABLE (ULDUM)
═══════════════════════════════════════════════════════════ */
  :root {
    --ice: #00e1ff;
    --ice-soft: #b3f4ff;
    --ice-dim: #008ba3;
    --ice-glow: rgba(0, 225, 255, 0.4);
    
    --amber: #f5a623;
    --amber-soft: #ffd97d;
    --amber-glow: rgba(245, 166, 35, 0.4);
    
    --bg-page: #04070d; /* Fond Abyssal */
    --bg-card: rgba(10, 14, 24, 0.55);
    --bg-card-hover: rgba(15, 22, 36, 0.85);

    --text-title: #ffffff;
    --text-body: #a2b0c2;

    --border: rgba(255, 255, 255, 0.05);
    --border-highlight: rgba(255, 255, 255, 0.15);

    --font-title: 'Cinzel', serif;
    --font-body: 'Outfit', sans-serif;

    --transition: 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    --radius: 16px;
  }

  /* ═══════════════════════════════════════════════════════════
   RESET & BASE (Anti-Scroll Global)
═══════════════════════════════════════════════════════════ */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  img { max-width: 100%; display: block; }
  a { text-decoration: none; color: inherit; }

  html, body {
    width: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    background-color: var(--bg-page);
    color: var(--text-body);
    font-family: var(--font-body);
  }

  .cw-page {
    position: relative;
    width: 100%;
    overflow-x: hidden;
  }

  /* Auras magiques globales (très douces) */
  .cw-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background: 
      radial-gradient(circle at 15% 20%, rgba(0, 225, 255, 0.08) 0%, transparent 50%),
      radial-gradient(circle at 85% 80%, rgba(245, 166, 35, 0.08) 0%, transparent 50%);
    mix-blend-mode: screen;
    pointer-events: none;
    z-index: 0;
    transform: translateZ(0); 
  }

  .cw-wrap {
    width: 100%;
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 clamp(1.5rem, 5vw, 4rem);
    position: relative;
    z-index: 2;
  }

  /* ═══════════════════════════════════════════════════════════
   HERO CINÉMATIQUE
═══════════════════════════════════════════════════════════ */
  .hero-showcase {
    position: relative;
    min-height: calc(100vh - var(--nav-h, 76px));
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: var(--nav-h, 76px);
    padding-bottom: 4rem; 
    background-color: var(--bg-page);
    
    background-image: 
      linear-gradient(to bottom, transparent 65%, var(--bg-page) 100%),
      url("<?= $template['location'] . 'assets/images/lk_no_background.png'; ?>");
    background-size: 100% 100%, cover;
    background-position: bottom, center 20%;
    background-repeat: no-repeat;
  }

  .hero-content {
    position: relative;
    z-index: 3;
    text-align: center;
    max-width: 850px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
  }

  .hero-logo img {
    height: clamp(120px, 15vw, 220px);
    margin: 0 auto;
    filter: drop-shadow(0 0 30px rgba(0, 225, 255, 0.2));
  }

  /* ── LE FAMEUX FOND LISSÉ ET FONDU DERRIÈRE LE TEXTE ── */
  .hero-text {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 2rem 1rem;
    position: relative;
    z-index: 1;
  }

  .hero-text::before {
    content: '';
    position: absolute;
    top: -20%; bottom: -20%; left: -15%; right: -15%;
    background: radial-gradient(ellipse at center, rgba(2, 5, 10, 0.85) 0%, rgba(2, 5, 10, 0.5) 45%, transparent 75%);
    filter: blur(25px);
    z-index: -1;
    pointer-events: none;
  }

  /* Le sous-titre majestueux au-dessus du titre principal */
  .hero-eyebrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 0.5rem;
  }
  .hero-eyebrow-line {
    height: 1px; width: 60px;
    background: linear-gradient(90deg, transparent, var(--amber));
  }
  .hero-eyebrow-line:last-child {
    background: linear-gradient(-90deg, transparent, var(--amber));
  }
  .hero-eyebrow-text {
    font-family: var(--font-title);
    font-size: clamp(0.9rem, 2vw, 1.2rem);
    color: var(--amber);
    text-transform: uppercase;
    letter-spacing: 0.3em;
    font-weight: 700;
    text-shadow: 0 0 15px var(--amber-glow);
  }

  .hero-title {
    font-family: var(--font-title);
    font-size: clamp(2.5rem, 6vw, 5rem);
    line-height: 1.1;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-title); 
    text-shadow: 0 0 4px #000, 0 0 8px #000, 0 0 16px #000, 0 10px 40px rgba(0, 0, 0, 0.9);
  }

  .hero-title span {
    color: var(--ice);
    text-shadow: 0 0 4px #000, 0 0 25px rgba(0, 225, 255, 0.8), 0 10px 40px rgba(0, 0, 0, 0.9);
  }

  .hero-subtitle {
    font-size: clamp(1.1rem, 2vw, 1.25rem);
    font-weight: 500; 
    max-width: 650px;
    margin: 0 auto;
    line-height: 1.6;
    color: #f8fafc; 
    text-shadow: 0 0 3px #000, 0 0 6px #000, 0 5px 20px rgba(0, 0, 0, 0.8);
  }

  .hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 10px;
    position: relative;
    z-index: 2;
  }

  /* ═══════════════════════════════════════════════════════════
   STATUT SERVEUR
═══════════════════════════════════════════════════════════ */
  .hero-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    background: rgba(10, 16, 28, 0.6);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid var(--border);
    padding: 0.6rem 1.8rem;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #fff;
    box-shadow: 0 10px 20px rgba(0,0,0,0.5);
    transition: var(--transition);
    transform: translateZ(0);
    margin-top: 15px;
  }

  a.hero-status-pill:hover {
    border-color: rgba(255, 255, 255, 0.15);
    background: rgba(0, 225, 255, 0.05);
    transform: translateY(-2px) translateZ(0);
    box-shadow: 0 15px 25px rgba(0,0,0,0.6), 0 0 15px rgba(0, 225, 255, 0.2);
    color: #fff;
  }

  .status-dot-modern { width: 10px; height: 10px; border-radius: 50%; }
  .status-dot-modern.online { background: #00ff88; box-shadow: 0 0 10px #00ff88, 0 0 20px #00ff88; animation: pulse-dot 2s infinite; }
  .status-dot-modern.offline { background: #ff4444; box-shadow: 0 0 10px #ff4444; }

  @keyframes pulse-dot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 255, 136, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(0, 255, 136, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 255, 136, 0); }
  }

  /* ═══════════════════════════════════════════════════════════
   BOUTONS MODERNES
═══════════════════════════════════════════════════════════ */
  .btn-epic {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem;
    padding: 1rem 2.5rem; font-family: var(--font-title); font-size: 1.1rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase; color: #01050a !important;
    background: linear-gradient(135deg, var(--ice), var(--ice-dim)); border-radius: 4px;
    transition: var(--transition); position: relative; overflow: hidden;
    box-shadow: 0 0 20px rgba(0, 225, 255, 0.2); transform: translateZ(0);
  }
  .btn-epic::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
    transform: skewX(-20deg); transition: var(--transition);
  }
  .btn-epic:hover { background: linear-gradient(135deg, var(--ice-soft), var(--ice)); box-shadow: 0 0 30px rgba(0, 225, 255, 0.4); transform: translateY(-2px) translateZ(0); }
  .btn-epic:hover::before { left: 200%; transition: 0.7s; }

  .btn-outline {
    display: inline-flex; padding: 1rem 2.5rem; font-family: var(--font-title); font-size: 1.1rem;
    font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #fff !important;
    background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); border-radius: 4px;
    transition: var(--transition); backdrop-filter: blur(5px); transform: translateZ(0);
  }
  .btn-outline:hover { border-color: rgba(255, 255, 255, 0.15); background: rgba(255, 255, 255, 0.08); box-shadow: 0 0 15px rgba(255,255,255,0.05); }

  /* ═══════════════════════════════════════════════════════════
   BENTO GRID & LUMIÈRES ATMOSPHÉRIQUES
═══════════════════════════════════════════════════════════ */
  .section-showcase { padding: 2rem 0 4rem; position: relative; z-index: 5; }

  .section-header { margin-bottom: 3rem; text-align: center; }
  .section-header h2 { font-family: var(--font-title); font-size: clamp(1.8rem, 3vw, 2.5rem); color: #fff; text-transform: uppercase; letter-spacing: 0.05em; }
  .section-header p { font-size: 1.1rem; color: var(--amber); text-transform: uppercase; letter-spacing: 0.2em; margin-bottom: 0.5rem; font-weight: 600; }

  .bento-atmosphere { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100vw; height: 0; pointer-events: none; z-index: 0; }
  .bento-atmosphere::before, .bento-atmosphere::after { content: ''; position: absolute; top: 0; height: 1px; filter: blur(2px); }
  .bento-atmosphere::before { left: -5vw; width: 55vw; background: var(--ice); box-shadow: 0 0 50px 25px rgba(0, 225, 255, 0.15); }
  .bento-atmosphere::after { right: -5vw; width: 55vw; background: var(--amber); box-shadow: 0 0 50px 25px rgba(245, 166, 35, 0.15); }

  /* Grille 3 colonnes de base */
  .bento-grid { display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: auto auto; gap: 1.5rem; position: relative; z-index: 2; margin-bottom: 3rem;}
  
  /* Grille support (Asymétrique 1/3 - 2/3) */
  .support-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; position: relative; z-index: 2;}

  .bento-card {
    background: var(--bg-card); border: 1px solid var(--border); border-top: 1px solid var(--border-highlight); 
    border-radius: var(--radius); padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;
    gap: 1.5rem; backdrop-filter: blur(15px); transition: var(--transition); position: relative;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); transform: translateZ(0); 
  }

  .bento-card::before {
    content: ''; position: absolute; inset: 0; border-radius: var(--radius); padding: 2px; 
    background: linear-gradient(135deg, var(--ice), transparent 40%, transparent 60%, var(--amber));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.5s ease; pointer-events: none; z-index: 2;
  }
  .bento-card::after {
    content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.03) 0%, transparent 70%);
    opacity: 0.5; transition: opacity 0.4s ease; pointer-events: none; z-index: 1;
  }

  .bento-card > div, .bento-card > a { position: relative; z-index: 3; }
  .bento-card:hover { transform: translateY(-4px); background: var(--bg-card-hover); border-color: var(--border-highlight); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7), 0 -10px 40px -10px rgba(0, 225, 255, 0.08); }
  .bento-card:hover::before, .bento-card:hover::after { opacity: 1; }

  .bento-large { grid-column: span 2; grid-row: span 2; }
  .bento-medium { grid-column: span 1; }

  /* Éléments internes de la carte */
  .bento-label {
    display: inline-block; font-family: var(--font-title); font-size: 0.85rem; letter-spacing: 0.25em;
    text-transform: uppercase; color: var(--amber); margin-bottom: 1rem; font-weight: 700;
  }

  .bento-icon {
    font-size: 2.2rem; margin-bottom: 1rem; display: inline-block; color: var(--text-body); opacity: 0.8;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), color 0.4s;
  }
  .bento-card:hover .bento-icon { transform: scale(1.15) rotate(-5deg); color: var(--ice); opacity: 1; filter: drop-shadow(0 0 10px var(--ice-glow)); }

  .bento-title { font-family: var(--font-title); font-size: 1.5rem; color: #fff; text-transform: uppercase; margin-bottom: 0.8rem; }
  .bento-large .bento-title { font-size: 2.2rem; }

  .bento-desc { font-size: 1.05rem; line-height: 1.7; color: var(--text-body); }
  .bento-large .bento-desc { font-size: 1.15rem; max-width: 85%; }

  .bento-link {
    display: inline-flex; align-items: center; gap: 0.5rem; color: #fff; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.9rem; transition: var(--transition); margin-top: auto;
  }
  .bento-link:hover { gap: 1rem; color: var(--ice); }
  .bento-link i { color: var(--text-body); transition: color 0.4s; }
  .bento-card:hover .bento-link i { color: var(--ice); }

  /* ═══════════════════════════════════════════════════════════
   BANNIÈRE DISCORD
═══════════════════════════════════════════════════════════ */
  .discord-banner {
    background: linear-gradient(90deg, rgba(88, 101, 242, 0.1), rgba(10, 14, 24, 0.8));
    border: 1px solid rgba(88, 101, 242, 0.2); border-radius: var(--radius);
    padding: 3rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;
    position: relative; z-index: 2; height: 100%; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
  }
  .discord-banner-content h3 { font-family: var(--font-title); color: #fff; font-size: 1.8rem; margin-bottom: 0.5rem; }
  .discord-banner-content p { color: var(--text-body); max-width: 500px; line-height: 1.6;}
  .btn-discord {
    background: #5865F2; color: #fff !important; padding: 1rem 2rem; border-radius: 8px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 0.8rem; transition: var(--transition); white-space: nowrap;
  }
  .btn-discord:hover { background: #4752c4; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(88, 101, 242, 0.3); }

  /* ═══════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════ */
  @media (max-width: 992px) {
    .bento-grid { grid-template-columns: 1fr; }
    .support-grid { grid-template-columns: 1fr; }
    .bento-large, .bento-medium { grid-column: span 1; grid-row: auto; }
    .bento-large .bento-desc { max-width: 100%; }
    .bento-atmosphere { display: none; } 
  }
  @media (max-width: 768px) {
    .hero-eyebrow-text { font-size: 0.8rem; }
    .hero-title { font-size: 2.2rem; }
    .hero-content { flex-direction: column; }
    .discord-banner { flex-direction: column; text-align: center; justify-content: center; }
  }
</style>

<div class="cw-page">

  <header class="hero-showcase">
    <div class="cw-wrap">
      <div class="hero-content">
        
        <div class="hero-logo">
          <img src="<?= $template['location'] . 'assets/images/logo.png'; ?>" alt="Celestia-WoW">
        </div>

        <div class="hero-text">
          <div class="hero-eyebrow">
             <span class="hero-eyebrow-line"></span>
             <span class="hero-eyebrow-text">Serveur Custom World of Warcraft</span>
             <span class="hero-eyebrow-line"></span>
          </div>

          <h1 class="hero-title">Forge ta <span>Légende</span></h1>
          
          <p class="hero-subtitle">
            Crée ta propre histoire. Deviens une légende dans un contenu custom exclusif.<br>
            <em style="color: var(--amber); font-style: normal; display: inline-block; margin-top: 5px;">Seul on va plus vite, Nombreux on va plus loin !</em>
          </p>
        </div>

        <div class="hero-actions">
          <a href="https://celestia-wow.com/en/register" class="btn-epic">Créer mon compte</a>
          <a href="https://celestia-wow.com/download" class="btn-outline">Télécharger</a>
        </div>

        <?php if ($this->wowmodule->getRealmStatus()): ?>
          <?php foreach ($realmsList as $charsMultiRealm):
            $multiRealm = $this->wowrealm->getRealmConnectionData($charsMultiRealm->id);
            $isOnline   = $this->wowrealm->RealmStatus(
              $charsMultiRealm->realmID,
              $this->wowrealm->realmGetHostname($charsMultiRealm->realmID)
            );
          ?>
            <?php if ($isOnline): ?>
              <a href="<?= base_url('online'); ?>" class="hero-status-pill">
                <span class="status-dot-modern online"></span>
                <span><?= $this->wowrealm->getRealmName($charsMultiRealm->realmID); ?> : <?= $this->wowrealm->getCharactersOnlineAlliance($multiRealm) + $this->wowrealm->getCharactersOnlineHorde($multiRealm); ?> Joueurs</span>
              </a>
            <?php else: ?>
              <div class="hero-status-pill">
                <span class="status-dot-modern offline"></span>
                <span style="color:#ff4444">Serveur Hors Ligne</span>
              </div>
            <?php endif ?>
          <?php endforeach ?>
        <?php endif ?>

      </div>
    </div>
  </header>

  <section class="section-showcase">
    <div class="cw-wrap">
      
      <div class="section-header">
        <p>L'aventure commence</p>
        <h2>Entrez dans le royaume</h2>
      </div>

      <div style="position: relative;">
        <div class="bento-atmosphere"></div>
        
        <div class="bento-grid">
          <div class="bento-card bento-large">
            <div>
              <span class="bento-label">L'univers Celestia</span>
              <h3 class="bento-title">Découvre nos ajouts et modifications custom</h3>
              <p class="bento-desc">Explore des quêtes, classes et zones entièrement inédites, conçues par l'équipe Celestia exclusivement pour toi. Un monde qui ne ressemble à aucun autre.</p>
            </div>
            <a href="https://celestia-wow.com/en/modifications" class="bento-link">En savoir plus <i class="fas fa-arrow-right"></i></a>
            <i class="fas fa-dragon bento-icon" style="position:absolute; top:2.5rem; right:2.5rem; font-size:3.5rem; opacity:0.2;"></i>
          </div>

          <div class="bento-card bento-medium">
            <div>
              <i class="fas fa-gem bento-icon"></i>
              <span class="bento-label">Visite la boutique</span>
              <h3 class="bento-title">Une promo tous les jours !</h3>
              <p class="bento-desc">Découvre la boutique et améliore ton confort de jeu grâce à des exclusivités Celestia surpuissantes.</p>
            </div>
            <a href="<?= base_url('store'); ?>" class="bento-link">Boutique <i class="fas fa-arrow-right"></i></a>
          </div>

          <div class="bento-card bento-medium">
            <div>
              <i class="fas fa-shield-alt bento-icon"></i>
              <span class="bento-label">Passe au niveau max</span>
              <h3 class="bento-title">Sésames & Starters</h3>
              <p class="bento-desc">Accède dès maintenant au contenu HL et obtiens un équipement adapté à ta spécialisation.</p>
            </div>
            <a href="https://celestia-wow.com/en/store/5" class="bento-link">Voir les offres <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </div>

    </div>
  </section>

  <section class="section-showcase" style="background: rgba(2,5,10,0.4); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="cw-wrap">
      
      <div class="section-header" style="text-align: center;">
        <p>Communauté & Évolution</p>
        <h2>Fais grandir Celestia</h2>
      </div>

      <div class="bento-grid">
        <div class="bento-card">
          <div>
            <i class="fas fa-paw bento-icon"></i>
            <span class="bento-label">Nouveauté boutique</span>
            <h3 class="bento-title">Token Monture</h3>
            <p class="bento-desc">Achète la monture de tes rêves directement en jeu. De WOTLK à Dragonflight, des dizaines de montures vous attendent à Tol'Vir.</p>
          </div>
          <a href="https://celestia-wow.com/en/store/15" class="bento-link">Découvrir <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="bento-card">
          <div>
            <i class="fas fa-heart bento-icon"></i>
            <span class="bento-label">Soutenir le projet</span>
            <h3 class="bento-title">Contributeur Celestia</h3>
            <p class="bento-desc">Accède dès maintenant à des avantages en jeu exclusifs grâce au Pack Donateur. Chaque contribution aide le serveur à vivre.</p>
          </div>
          <a href="<?= base_url('donate'); ?>" class="bento-link">Contribuer <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="bento-card">
          <div>
            <i class="fas fa-star bento-icon"></i>
            <span class="bento-label">Promouvoir Celestia</span>
            <h3 class="bento-title">Vote et gagne des Points !</h3>
            <p class="bento-desc">Grâce à vos votes, Celestia évolue dans le classement. Chaque clic contribue à faire grandir la communauté.</p>
          </div>
          <a href="https://celestia-wow.com/en/vote" class="bento-link">Voter maintenant <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

    </div>
  </section>

  <section class="section-showcase">
    <div class="cw-wrap">
      
      <div class="support-grid">
        <div class="bento-card">
          <div>
            <i class="fas fa-bug bento-icon"></i>
            <span class="bento-label">BugTracker</span>
            <h3 class="bento-title">Tu rencontres un bug ?</h3>
            <p class="bento-desc">Signale un bug directement via notre outil de tickets ou contacte un Maître du Jeu sur Discord. Votre expérience est notre priorité absolue.</p>
          </div>
          <a href="https://discord.com/channels/1084947995335872563/1084956206872928359" class="bento-link">Signaler un bug <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if ($this->wowmodule->getDiscordStatus() == '1'): ?>
        <div class="discord-banner">
          <div class="discord-banner-content">
            <h3>Rejoins la communauté</h3>
            <p>PARTAGE PLUS QUE LE JEU ! <br>Retrouve toutes les infos, suis l'actualité du serveur sur notre discord communautaire et interagis avec les autres joueurs et le staff.</p>
          </div>
          <a href="https://discord.gg/<?= $this->config->item('discord_invitation'); ?>" target="_blank" class="btn-discord">
            <i class="fab fa-discord" style="font-size:1.5rem;"></i> Rejoindre Discord
          </a>
        </div>
        <?php endif ?>
      </div>

    </div>
  </section>

</div>