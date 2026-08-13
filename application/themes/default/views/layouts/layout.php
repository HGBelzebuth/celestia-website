<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $this->config->item('website_name'); ?> — <?= $pagetitle ?></title>
	<?= $template['metadata']; ?>
	<link rel="icon" type="image/x-icon" href="<?= $template['location'] . 'assets/images/favicon.ico'; ?>" />

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

	<link rel="stylesheet" href="<?= $template['assets'] . 'core/uikit/css/uikit.min.css'; ?>">
	<link rel="stylesheet" href="<?= $template['location'] . 'assets/css/main.css'; ?>">
	<link href="<?= $template['assets'] . 'core/css/core.f0a1c125520131b6cb88.css'; ?>" rel="stylesheet">
	<link href="<?= $template['assets'] . 'core/css/3.130ed27842b953a05ff8.css'; ?>" rel="stylesheet">
	<script src="<?= $template['assets'] . 'core/uikit/js/uikit.min.js'; ?>"></script>
	<script src="<?= $template['assets'] . 'core/uikit/js/uikit-icons.min.js'; ?>"></script>
	<script src="<?= $template['assets'] . 'core/js/core.66a3d362300af79ba147.js'; ?>"></script>
	<script id="init">
		window.trigger && window.trigger("init");
	</script>

	<style>
		/* ═══════════════════════════════════════════════════════
   TOKENS 2026 : HYBRIDE GIVRE & SABLE (WOTLK x ULDUM)
═══════════════════════════════════════════════════════ */
		:root {
			/* Pôle Froid (Givre) */
			--ice: #00e1ff;
			--ice-soft: #b3f4ff;
			--ice-dim: #008ba3;
			--ice-pale: rgba(0, 225, 255, .08);
			--ice-glow: rgba(0, 225, 255, .4);
			--ice-glow-strong: rgba(0, 225, 255, .8);

			/* Pôle Chaud (Sable / Uldum) */
			--amber: #f5a623;
			--amber-soft: #ffd97d;
			--amber-dim: #b37717;
			--amber-pale: rgba(245, 166, 35, 0.08);
			--amber-glow: rgba(245, 166, 35, 0.4);

			/* Fonds Abyssaux */
			--bg-page: #04070d;
			--bg-deep: #020408;
			--bg-card: rgba(10, 14, 24, .92);

			/* Textes */
			--text-hi: #ffffff;
			--text-mid: #a2b0c2;
			--text-lo: #5c6b7f;
			--text-ice: #80eaff;
			--text-amber: #ffd97d;

			/* Bordures */
			--border: rgba(255, 255, 255, 0.05);
			--border-lo: rgba(255, 255, 255, 0.08);
			--border-highlight: rgba(255, 255, 255, 0.15);

			--font-rune: 'Cinzel', Georgia, serif;
			--font-prose: 'Outfit', sans-serif;

			--nav-h: 76px;
			--nav-bg: rgba(4, 7, 13, 0.75);

			--ease-out: cubic-bezier(.22, 1, .36, 1);
			--t: .3s;
		}

		/* ── Base ── */
		*,
		*::before,
		*::after {
			box-sizing: border-box;
			margin: 0;
			padding: 0
		}

		html {
			scroll-behavior: smooth;
			overflow-x: hidden;
		}

		body {
			background: var(--bg-page);
			font-family: var(--font-prose);
			color: var(--text-hi);
			min-height: 100vh;
			font-size: 1.05rem;
			line-height: 1.65;
			overflow-x: hidden;
		}

		a {
			text-decoration: none;
			color: inherit
		}

		img {
			display: block;
			max-width: 100%
		}

		::-webkit-scrollbar {
			width: 6px
		}

		::-webkit-scrollbar-track {
			background: var(--bg-deep)
		}

		::-webkit-scrollbar-thumb {
			background: var(--ice-dim);
			border-radius: 3px;
		}
		::-webkit-scrollbar-thumb:hover {
			background: var(--ice);
		}

		/* ═══════════════════════════════════════════════════════
   NAVBAR (Hybride)
═══════════════════════════════════════════════════════ */
		.nav {
			position: fixed;
			inset-inline: 0;
			top: 0;
			z-index: 9000;
			height: var(--nav-h);
			background: var(--nav-bg); 
			backdrop-filter: blur(16px) saturate(180%);
			-webkit-backdrop-filter: blur(16px) saturate(180%);
			border-bottom: 1px solid rgba(255, 255, 255, 0.05);
			transition: background var(--t), border-color var(--t), box-shadow var(--t);
		}

		.nav::before {
			content: '';
			position: absolute;
			inset: 0;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='.025'/%3E%3C/svg%3E");
			opacity: .3;
			pointer-events: none;
		}

		.nav.is-scrolled {
			background: rgba(2, 4, 8, 0.90);
			border-bottom-color: rgba(255, 255, 255, 0.1);
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
		}

		@media (max-width: 768px) {
			.nav {
				backdrop-filter: none;
				-webkit-backdrop-filter: none;
				background: rgba(2, 4, 8, .98);
			}
			.nav::before {
				display: none;
			}
			.nav.is-scrolled {
				box-shadow: 0 1px 12px rgba(0, 0, 0, .8);
			}
			.nav {
				transition: none;
			}
			.nav__ice-line {
				transition: none;
			}
		}

		/* Ligne d'animation au scroll avec dégradé Hybride Cyan -> Ambre */
		.nav__ice-line {
			position: absolute;
			bottom: -1px;
			left: 0;
			right: 0;
			height: 1px;
			background: linear-gradient(90deg, transparent 0%, var(--ice-dim) 20%, var(--ice) 50%, var(--amber) 80%, transparent 100%);
			transform: scaleX(0);
			transform-origin: center;
			transition: transform .6s var(--ease-out);
			pointer-events: none;
			box-shadow: 0 0 8px var(--ice-glow);
		}

		.nav.is-scrolled .nav__ice-line {
			transform: scaleX(1)
		}

		.nav__wrap {
			height: 100%;
			max-width: 1400px;
			margin: auto;
			padding: 0 clamp(1.25rem, 4vw, 3rem);
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		/* Logo */
		.nav__logo {
			display: flex;
			align-items: center;
			gap: .75rem;
			flex-shrink: 0
		}

		.nav__logo-img {
			height: clamp(40px, 5vw, 52px);
			width: auto;
			filter: drop-shadow(0 0 8px var(--ice-pale));
			transition: filter var(--t), transform var(--t) var(--ease-out);
		}

		.nav__logo:hover .nav__logo-img {
			filter: drop-shadow(0 0 15px var(--ice-glow));
			transform: scale(1.05);
		}

		/* Liens */
		.nav__links {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			list-style: none;
			margin: 0 auto;
		}

		.nav__item { position: relative }

		.nav__link {
			display: flex;
			flex-direction: row;
			align-items: center;
			gap: 0.5rem;
			padding: 0.5rem 1rem;
			font-family: var(--font-rune);
			font-size: 0.9rem;
			font-weight: 600;
			letter-spacing: 0.1em;
			text-transform: uppercase;
			color: var(--text-mid);
			border-radius: 6px;
			cursor: pointer;
			transition: all var(--t);
			white-space: nowrap;
			position: relative;
			user-select: none;
		}

		.nav__link .ni {
			font-size: 0.95rem;
			color: var(--text-lo);
			transition: color var(--t), transform var(--t);
		}

		/* Survol Classique (Givre) */
		.nav__link:hover,
		.nav__link.active {
			color: var(--text-hi);
			background: var(--ice-pale);
		}
		.nav__link:hover .ni,
		.nav__link.active .ni {
			color: var(--ice);
			filter: drop-shadow(0 0 5px var(--ice-glow));
		}
		.nav__link.active::after {
			content: '';
			position: absolute;
			bottom: 4px;
			left: 50%;
			transform: translateX(-50%);
			width: 30px;
			height: 2px;
			background: var(--ice);
			border-radius: 2px;
			box-shadow: 0 0 8px var(--ice-glow);
		}

		/* Survol Variante Uldum (Ambre) */
		.nav__link.nav__link--amber:hover,
		.nav__link.nav__link--amber.active {
			background: var(--amber-pale);
		}
		.nav__link.nav__link--amber:hover .ni,
		.nav__link.nav__link--amber.active .ni {
			color: var(--amber);
			filter: drop-shadow(0 0 5px var(--amber-glow));
		}
		.nav__link.nav__link--amber.active::after {
			background: var(--amber);
			box-shadow: 0 0 8px var(--amber-glow);
		}

		/* Dropdown */
		.nav__drop {
			position: absolute;
			top: calc(100% + 5px);
			left: 50%;
			transform: translateX(-50%) translateY(-10px);
			min-width: 220px;
			background: rgba(2, 4, 8, .95);
			border: 1px solid var(--border-lo);
			border-top: 2px solid var(--ice);
			border-radius: 0 0 8px 8px;
			box-shadow: 0 20px 50px rgba(0, 0, 0, .8), 0 0 20px var(--ice-pale);
			backdrop-filter: blur(20px);
			padding: .75rem 0;
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transition: all var(--t) var(--ease-out);
			transition-delay: 150ms;
			z-index: 10;
		}

		.nav__drop.nav__drop--amber {
			border-top: 2px solid var(--amber);
			box-shadow: 0 20px 50px rgba(0, 0, 0, .8), 0 0 20px var(--amber-pale);
		}

		.nav__drop::after {
			content: '';
			position: absolute;
			top: -15px;
			left: 0;
			right: 0;
			height: 15px;
		}

		.nav__item:hover .nav__drop,
		.nav__item:focus-within .nav__drop {
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transform: translateX(-50%) translateY(0);
			transition-delay: 0s;
		}

		.nav__drop-item {
			display: flex;
			align-items: center;
			gap: .8rem;
			padding: .7rem 1.25rem;
			font-family: var(--font-rune);
			font-size: 0.95rem;
			letter-spacing: .12em;
			text-transform: uppercase;
			color: var(--text-mid);
			transition: all var(--t);
		}

		.nav__drop-item .ni {
			width: 20px;
			text-align: center;
			color: var(--text-lo);
			font-size: 1.05rem;
			transition: color var(--t);
			flex-shrink: 0
		}

		.nav__drop-item:hover {
			color: var(--ice-soft);
			background: linear-gradient(90deg, var(--ice-pale), transparent);
			padding-left: 1.6rem;
			border-left: 2px solid var(--ice);
		}

		.nav__drop-item:hover .ni {
			color: var(--ice);
			filter: drop-shadow(0 0 5px var(--ice-glow));
		}

		.nav__drop--amber .nav__drop-item:hover {
			color: var(--amber-soft);
			background: linear-gradient(90deg, var(--amber-pale), transparent);
			border-left-color: var(--amber);
		}

		.nav__drop--amber .nav__drop-item:hover .ni {
			color: var(--amber);
			filter: drop-shadow(0 0 5px var(--amber-glow));
		}

		.nav__drop-sep {
			height: 1px;
			background: var(--border-lo);
			margin: .5rem 1rem
		}

		/* Droite */
		.nav__right {
			display: flex;
			align-items: center;
			gap: 1rem;
			flex-shrink: 0;
		}

		.nav__points {
			display: flex;
			align-items: center;
			gap: .5rem;
			padding: .35rem .95rem;
			background: rgba(0, 225, 255, .03);
			border: 1px solid var(--border-lo);
			border-radius: 20px;
			font-family: var(--font-rune);
			font-size: 1rem;
			letter-spacing: .1em;
			color: var(--text-hi);
			transition: all var(--t);
		}

		.nav__points:hover {
			border-color: var(--ice-dim);
			background: var(--ice-pale);
			box-shadow: 0 0 10px var(--ice-pale);
		}

		.nav__account-wrap { position: relative }

		/* Bouton Connexion / Compte */
		.nav__account {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.45rem 1.1rem;
			font-family: var(--font-rune);
			font-size: 0.85rem;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: var(--text-hi);
			border: 1px solid rgba(255, 255, 255, 0.15);
			border-radius: 4px;
			background: transparent;
			cursor: pointer;
			transition: all var(--t);
			white-space: nowrap;
		}

		.nav__account:hover {
			background: rgba(255, 255, 255, 0.05);
			border-color: var(--ice-dim);
			color: var(--ice-soft);
		}

		.nav__account .cw-avatar {
			width: 28px;
			height: 28px;
			border-radius: 50%;
			border: 1px solid var(--ice-dim);
			object-fit: cover
		}

		.nav__account .ni {
			font-size: 1.1rem;
			color: var(--ice-dim)
		}

		.nav__account-dd {
			position: absolute;
			top: calc(100% + 15px);
			right: 0;
			min-width: 220px;
			background: rgba(2, 4, 8, .95);
			border: 1px solid var(--border-lo);
			border-top: 2px solid var(--ice);
			border-radius: 0 0 8px 8px;
			box-shadow: 0 20px 50px rgba(0, 0, 0, .8), 0 0 20px var(--ice-pale);
			backdrop-filter: blur(20px);
			padding: .75rem 0;
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transform: translateY(-10px);
			transition: all var(--t) var(--ease-out);
			z-index: 10;
		}

		.nav__account-wrap:hover .nav__account-dd,
		.nav__account-wrap:focus-within .nav__account-dd {
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transform: translateY(0);
		}

		/* Bouton Inscription */
		.nav__register {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.5rem 1.4rem;
			font-family: var(--font-rune);
			font-size: 0.85rem;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			background: linear-gradient(135deg, var(--ice), var(--ice-dim));
			color: #010306;
			border: none;
			border-radius: 4px;
			font-weight: 700;
			transition: all var(--t);
			white-space: nowrap;
			box-shadow: 0 4px 15px rgba(0, 225, 255, 0.25);
		}

		.nav__register:hover {
			background: linear-gradient(135deg, #fff, var(--ice));
			box-shadow: 0 6px 20px rgba(0, 225, 255, 0.5);
			transform: translateY(-1px);
		}

		.nav__register .ni { font-size: 1rem; color: #010306; }

		/* Burger */
		.nav__burger {
			display: none;
			flex-direction: column;
			justify-content: center;
			gap: 5px;
			width: 42px;
			height: 42px;
			padding: .5rem;
			background: transparent;
			border: 1px solid var(--border-lo);
			border-radius: 6px;
			cursor: pointer;
			margin-left: auto;
			transition: all var(--t);
		}

		.nav__burger:hover {
			background: var(--ice-pale);
			border-color: var(--ice-dim);
			box-shadow: 0 0 10px var(--ice-glow);
		}

		.nav__burger span {
			display: block;
			width: 100%;
			height: 2px;
			background: var(--text-mid);
			border-radius: 2px;
			transition: all var(--t) var(--ease-out);
		}

		.nav__burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); background: var(--ice); }
		.nav__burger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
		.nav__burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); background: var(--ice); }

		/* Menu mobile */
		.nav__mobile {
			position: fixed;
			inset-inline: 0;
			top: var(--nav-h);
			background: rgba(2, 4, 8, .98);
			backdrop-filter: blur(20px);
			border-bottom: 1px solid var(--border-lo);
			padding: 1rem clamp(1.25rem, 4vw, 3rem) 2rem;
			max-height: calc(100svh - var(--nav-h));
			overflow-y: auto;
			display: none;
			opacity: 0;
			transition: opacity .32s var(--ease-out);
			z-index: 8999;
		}

		.nav__mobile.open { display: block; opacity: 1; }

		.nav__mobile-list { display: flex; flex-direction: column; gap: .2rem }

		.nav__mobile-link {
			display: flex;
			align-items: center;
			gap: .8rem;
			padding: .85rem 1rem;
			font-family: var(--font-rune);
			font-size: 1rem;
			letter-spacing: .15em;
			text-transform: uppercase;
			color: var(--text-mid);
			border-radius: 6px;
			transition: all var(--t);
		}

		.nav__mobile-link .ni {
			width: 20px;
			text-align: center;
			color: var(--text-lo);
			font-size: 1.1rem;
			transition: color var(--t)
		}

		.nav__mobile-link:hover {
			color: var(--ice);
			background: linear-gradient(90deg, var(--ice-pale), transparent);
			padding-left: 1.5rem;
			border-left: 2px solid var(--ice);
		}

		.nav__mobile-link:hover .ni { color: var(--ice); filter: drop-shadow(0 0 5px var(--ice-glow)); }

		.nav__mobile-sep { height: 1px; background: var(--border-lo); margin: .6rem 0 }

		.cw-hero { padding-top: calc(var(--nav-h) + clamp(3.5rem, 9vh, 6rem)) !important }
		#cw-main { padding-top: 0 }

		/* ═══════════════════════════════════════════════════════
   FOOTER (Épuré et Fondu)
═══════════════════════════════════════════════════════ */
		.site-footer {
			/* Fondu parfait du background de la page vers le bleu nuit très sombre du footer */
			background: linear-gradient(to bottom, var(--bg-page) 0%, var(--bg-deep) 15%, var(--bg-deep) 100%);
			position: relative;
			overflow: hidden;
			border-top: none; /* Suppression de la ligne dure en haut du footer */
			padding-top: 2rem; /* Espace pour le fondu */
		}

		/* Ligne de lumière extrêmement fine (1px) et subtile (opacité faible) */
		.site-footer::before {
			content: '';
			position: absolute;
			top: 0;
			left: 10%;
			right: 10%;
			height: 1px;
			/* Dégradé doux : rien -> cyan -> ambre -> rien */
			background: linear-gradient(90deg, transparent 0%, rgba(0, 225, 255, 0.15) 40%, rgba(245, 166, 35, 0.15) 60%, transparent 100%);
			pointer-events: none;
		}

		.site-footer__social {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 1rem;
			padding: 2.5rem clamp(1.25rem, 4vw, 3rem) 3rem;
			border-bottom: 1px solid rgba(255,255,255, 0.03); /* Bordure quasi invisible */
			position: relative;
		}

		.site-footer__social-label {
			font-family: var(--font-rune);
			font-size: 1rem;
			letter-spacing: .25em;
			text-transform: uppercase;
			color: var(--text-ice)
		}

		.site-footer__social-links { display: flex; gap: .8rem }

		.site-footer__social-btn {
			width: 42px;
			height: 42px;
			border-radius: 50%;
			border: 1px solid var(--border-lo);
			background: rgba(0, 225, 255, .03);
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--text-mid);
			font-size: 1.1rem;
			transition: all var(--t) var(--ease-out);
		}

		.site-footer__social-btn:hover {
			background: var(--ice-pale);
			border-color: var(--ice);
			color: var(--ice);
			transform: translateY(-4px);
			box-shadow: 0 5px 15px var(--ice-glow);
		}

		.site-footer__body {
			max-width: 1360px;
			margin: auto;
			padding: 3rem clamp(1.25rem, 4vw, 3rem);
			display: grid;
			grid-template-columns: 160px 1fr 160px;
			align-items: center;
			gap: 2rem;
			position: relative;
		}

		.site-footer__logo img {
			height: 60px;
			width: auto;
			filter: drop-shadow(0 0 10px rgba(0, 225, 255, .15));
			transition: filter var(--t)
		}

		.site-footer__logo:hover img { filter: drop-shadow(0 0 20px var(--ice-glow)) }

		.site-footer__links {
			display: flex;
			flex-wrap: wrap;
			justify-content: center;
			gap: .5rem 2rem;
			list-style: none
		}

		.site-footer__link {
			font-family: var(--font-rune);
			font-size: 0.95rem;
			letter-spacing: .15em;
			text-transform: uppercase;
			color: var(--text-mid);
			transition: color var(--t);
			position: relative;
			padding-bottom: 4px;
		}

		.site-footer__link::after {
			content: '';
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			height: 1px;
			background: var(--ice);
			transform: scaleX(0);
			transform-origin: left;
			transition: transform var(--t) var(--ease-out);
			box-shadow: 0 0 5px var(--ice-glow);
		}

		.site-footer__link:hover { color: var(--text-hi); text-shadow: 0 0 8px rgba(255,255,255,.2); }
		.site-footer__link:hover::after { transform: scaleX(1) }

		.site-footer__badge {
			display: flex;
			flex-direction: column;
			align-items: flex-end;
			gap: .5rem;
			font-family: var(--font-rune);
			font-size: 0.95rem;
			letter-spacing: .1em;
			text-transform: uppercase;
			color: var(--text-lo)
		}

		.site-footer__badge img {
			width: 42px;
			opacity: .4;
			filter: grayscale(1);
			transition: all var(--t)
		}

		.site-footer__badge:hover img {
			opacity: 1;
			filter: drop-shadow(0 0 10px var(--ice-glow));
		}

		.site-footer__bottom {
			border-top: 1px solid rgba(0, 225, 255, .05);
			background: rgba(0, 0, 0, .2);
			max-width: 100%;
		}
		
		.site-footer__bottom-wrap {
			max-width: 1360px;
			margin: auto;
			padding: 1.5rem clamp(1.25rem, 4vw, 3rem);
			display: flex;
			flex-wrap: wrap;
			justify-content: space-between;
			align-items: center;
			gap: 1rem;
		}

		.site-footer__copy {
			font-family: var(--font-rune);
			font-size: 0.95rem;
			letter-spacing: .08em;
			color: var(--text-mid)
		}

		.site-footer__copy strong { color: var(--text-ice) }

		.site-footer__copy a {
			color: var(--ice-dim);
			transition: color var(--t);
			text-decoration: underline;
			text-decoration-color: transparent;
		}

		.site-footer__copy a:hover {
			color: var(--ice);
			text-decoration-color: var(--ice);
		}

		.site-footer__legal {
			font-size: 0.9rem;
			color: var(--text-lo);
			max-width: 520px;
			text-align: right;
			line-height: 1.6
		}

		/* Responsive */
		@media(max-width:1100px) {
			.nav__links, .nav__points, .nav__account-wrap, .nav__register { display: none }
			.nav__burger { display: flex }
			.nav__wrap { justify-content: space-between; } 
			.nav__logo-text { display: none }
		}

		@media(max-width:640px) {
			:root { --nav-h: 70px }
			.site-footer__body {
				grid-template-columns: 1fr;
				justify-items: center;
				text-align: center
			}
			.site-footer__badge { align-items: center }
			.site-footer__bottom-wrap {
				flex-direction: column;
				text-align: center
			}
			.site-footer__legal {
				text-align: center;
				max-width: 100%
			}
		}

		/* ── Bulle panier (Style 2026) ── */
		.nav__cart-badge {
			position: absolute;
			top: -10px;
			right: -10px;
			min-width: 22px;
			height: 22px;
			padding: 0 5px;
			background: rgba(2, 6, 14, 0.95); 
			border: 1px solid var(--ice);
			color: var(--ice);
			font-family: var(--font-rune);
			font-size: 0.85rem;
			font-weight: 700;
			letter-spacing: 0;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			line-height: 1;
			pointer-events: none;
			animation: badge-pop .35s var(--ease-out), badge-float-cyan 3s ease-in-out infinite;
			box-shadow: 0 0 10px var(--ice-glow);
		}

		@keyframes badge-pop {
			0% { transform: translateY(0px) scale(0); opacity: 0; }
			65% { transform: translateY(-2px) scale(1.15); opacity: 1; }
			100% { transform: translateY(0px) scale(1); opacity: 1; }
		}

		@keyframes badge-float-cyan {
			0%, 100% {
				transform: translateY(0px);
				border-color: var(--ice);
				color: var(--ice);
				box-shadow: 0 0 5px rgba(0, 225, 255, 0.3);
				text-shadow: 0 0 5px rgba(0, 225, 255, 0.4);
			}
			50% {
				transform: translateY(-4px);
				border-color: #fff;
				color: #fff;
				box-shadow: 0 0 15px rgba(0, 225, 255, 0.8), 0 0 5px #fff;
				text-shadow: 0 0 10px #fff, 0 0 20px rgba(0, 225, 255, 0.8);
			}
		}

		.nav__drop-badge {
			margin-left: auto;
			min-width: 20px;
			height: 20px;
			padding: 0 6px;
			background: var(--ice-pale);
			border: 1px solid var(--ice-glow);
			color: var(--ice);
			font-family: var(--font-rune);
			font-size: .75rem;
			font-weight: 700;
			letter-spacing: 0;
			border-radius: 10px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			line-height: 1;
			box-shadow: 0 0 8px var(--ice-glow);
		}
	</style>
</head>

<body>

	<?php
	$__cartCount = 0;
	if (isset($this->cart)) {
		$__cartCount = (int) $this->cart->total_items();
	} else {
		$__cartContents = $this->session->userdata('cart_contents');
		if (is_array($__cartContents)) {
			foreach ($__cartContents as $__row) {
				if (is_array($__row) && isset($__row['qty'])) {
					$__cartCount += (int) $__row['qty'];
				}
			}
		}
	}
	?>
	<nav class="nav" id="cwNav" aria-label="Navigation principale">
		<div class="nav__ice-line"></div>
		<div class="nav__wrap">

			<a href="<?= base_url(); ?>" class="nav__logo">
				<img src="<?= $template['location'] . 'assets/images/pins-celestia.png'; ?>" alt="<?= $this->config->item('website_name'); ?>" class="nav__logo-img">
			</a>

			<ul class="nav__links" role="list">
				<li class="nav__item">
					<a href="<?= base_url(); ?>" class="nav__link"><i class="fas fa-scroll ni"></i>Accueil</a>
				</li>
				<li class="nav__item">
					<span class="nav__link" tabindex="0" role="button"><i class="fas fa-feather-alt ni"></i>Infos</span>
					<div class="nav__drop">
						<a href="<?= base_url('download'); ?>" class="nav__drop-item"><i class="fas fa-download ni"></i>Télécharger</a>
						<a href="<?= base_url('modifications'); ?>" class="nav__drop-item"><i class="fas fa-dragon ni"></i>Contenu custom</a>
						<a href="<?= base_url('pvp'); ?>" class="nav__drop-item"><i class="fab fa-rebel ni"></i>Arènes & PvP</a>
						<a href="<?= base_url('changelogs'); ?>" class="nav__drop-item"><i class="fab fa-medrt ni"></i>Changelog</a>
						<div class="nav__drop-sep"></div>
						<a href="<?= base_url('armory'); ?>" class="nav__drop-item"><i class="fas fa-shield-alt ni"></i>Armurerie</a>
					</div>
				</li>
				<li class="nav__item">
					<span class="nav__link nav__link--amber" tabindex="0" role="button"><i class="fas fa-gem ni"></i>Boutique</span>
					<div class="nav__drop nav__drop--amber">
						<a href="<?= base_url('store'); ?>" class="nav__drop-item"><i class="fas fa-gem ni"></i>Parcourir la boutique</a>
						<?php if ($this->wowauth->isLogged()): ?>
							<div class="nav__drop-sep"></div>
							<a href="<?= base_url('store/history'); ?>" class="nav__drop-item"><i class="fas fa-history ni"></i>Historique des achats</a>
						<?php endif ?>
					</div>
				</li>
				<li class="nav__item">
					<a href="https://celestia-wow.com/vote" class="nav__link"><i class="fas fa-star ni"></i>Voter</a>
				</li>
				<li class="nav__item">
					<a href="<?= base_url('cart'); ?>" class="nav__link"><i class="fa fa-shopping-cart ni"></i>Panier<?php if ($__cartCount > 0): ?><span class="nav__cart-badge"><?= $__cartCount ?></span><?php endif; ?></a>
				</li>
				<li class="nav__item">
					<a href="<?= base_url('donate'); ?>" class="nav__link nav__link--amber"><i class="fas fa-heart ni"></i>Contribuer</a>
				</li>
			</ul>

			<div class="nav__right">
				<?php if ($this->wowauth->isLogged()): ?>
					<div class="nav__points">
						<i class="dp-icon"></i>
						<span data-dp-display uk-tooltip="title:<?= $this->lang->line('panel_dp'); ?>;pos:bottom"><?= $this->wowgeneral->getCharDPTotal($this->session->userdata('wow_sess_id')); ?></span> <span style="color:var(--border-lo)">·</span>
						<i class="vp-icon"></i>
						<span uk-tooltip="title:<?= $this->lang->line('panel_vp'); ?>;pos:bottom"><?= $this->wowgeneral->getCharVPTotal($this->session->userdata('wow_sess_id')); ?></span>
					</div>
					<div class="nav__account-wrap">
						<button class="nav__account">
							<?php if ($this->wowmodule->getUCPStatus() == '1' && $this->wowgeneral->getUserInfoGeneral($this->session->userdata('wow_sess_id'))->num_rows()): ?>
								<img class="cw-avatar" src="<?= base_url('assets/images/profiles/' . $this->wowauth->getNameAvatar($this->wowauth->getImageProfile($this->session->userdata('wow_sess_id')))); ?>" alt="avatar">
							<?php else: ?>
								<i class="fas fa-user-circle ni"></i>
							<?php endif; ?>
							<span><?= $this->session->userdata('blizz_sess_username'); ?></span>
							<i class="fas fa-chevron-down" style="font-size:1rem;color:var(--ice-dim)"></i>
						</button>
						<div class="nav__account-dd">
							<?php if ($this->wowmodule->getUCPStatus() == '1'): ?>
								<a href="<?= base_url('panel'); ?>" class="nav__drop-item"><i class="fas fa-cog ni"></i><?= $this->lang->line('button_user_panel'); ?></a>
							<?php endif; ?>
							<?php if ($this->wowauth->getRank($this->session->userdata('wow_sess_id')) >= config_item('mod_access_level')): ?>
								<a href="<?= base_url('mod'); ?>" class="nav__drop-item"><i class="fas fa-shield-alt ni"></i><?= $this->lang->line('button_mod_panel'); ?></a>
							<?php endif; ?>
							<?php if ($this->wowmodule->getACPStatus() == '1' && $this->wowauth->getRank($this->session->userdata('wow_sess_id')) >= config_item('admin_access_level')): ?>
								<a href="<?= base_url('admin'); ?>" class="nav__drop-item"><i class="fas fa-cogs ni"></i><?= $this->lang->line('button_admin_panel'); ?></a>
							<?php endif; ?>
							<div class="nav__drop-sep"></div>
							<?php if ($__cartCount > 0): ?>
								<a href="<?= base_url('cart'); ?>" class="nav__drop-item"><i class="fa fa-shopping-cart ni"></i><?= $this->lang->line('button_view_cart'); ?><span class="nav__drop-badge"><?= $__cartCount ?></span></a>
							<?php else: ?>
								<span class="nav__drop-item" style="opacity:.4;cursor:default;"><i class="fa fa-shopping-cart ni"></i><?= $this->lang->line('store_cart_no_items'); ?></span>
							<?php endif; ?>
							<div class="nav__drop-sep"></div>
							<a href="<?= base_url('logout'); ?>" class="nav__drop-item" style="color:#ff4d4d"><i class="fas fa-sign-out-alt ni" style="color:#ff4d4d"></i><?= $this->lang->line('button_logout'); ?></a>
						</div>
					</div>
				<?php else: ?>
					<a href="<?= base_url('login'); ?>" class="nav__account"><i class="fas fa-sign-in-alt ni"></i>Connexion</a>
					<?php if ($this->wowmodule->getRegisterStatus() == '1'): ?>
						<a href="<?= base_url('register'); ?>" class="nav__register"><i class="fas fa-user-plus ni"></i>S'inscrire</a>
					<?php endif; ?>
				<?php endif; ?>
				<button class="nav__burger" id="cwBurger" aria-label="Menu" aria-expanded="false">
					<span></span><span></span><span></span>
				</button>
			</div>

		</div>
	</nav>

	<div class="nav__mobile" id="cwMobile" aria-hidden="true">
		<nav class="nav__mobile-list">
			<a href="<?= base_url(); ?>" class="nav__mobile-link"><i class="fas fa-scroll ni"></i>Accueil</a>
			<a href="<?= base_url('modifications'); ?>" class="nav__mobile-link"><i class="fas fa-dragon ni"></i>Contenu custom</a>
			<a href="<?= base_url('pvp'); ?>" class="nav__mobile-link"><i class="fas fa-swords ni"></i>PvP</a>
			<div class="nav__mobile-sep"></div>
			<a href="<?= base_url(); ?>" class="nav__mobile-link"><i class="fas fa-newspaper ni"></i>Actualités</a>
			<a href="<?= base_url('changelogs'); ?>" class="nav__mobile-link"><i class="fas fa-tasks ni"></i>Changelog</a>
			<a href="<?= base_url('armory'); ?>" class="nav__mobile-link"><i class="fas fa-shield-alt ni"></i>Armurerie</a>
			<a href="<?= base_url('forum'); ?>" class="nav__mobile-link"><i class="fas fa-comments ni"></i>Forum</a>
			<div class="nav__mobile-sep"></div>
			<a href="<?= base_url('store'); ?>" class="nav__mobile-link"><i class="fas fa-gem ni"></i>Boutique</a>
			<?php if ($this->wowauth->isLogged()): ?>
				<a href="<?= base_url('store/history'); ?>" class="nav__mobile-link"><i class="fas fa-history ni"></i>Historique des achats</a>
			<?php endif ?>
			<a href="https://celestia-wow.com/vote" class="nav__mobile-link"><i class="fas fa-star ni"></i>Voter</a>
			<a href="<?= base_url('download'); ?>" class="nav__mobile-link"><i class="fas fa-download ni"></i>Télécharger</a>
			<a href="<?= base_url('donate'); ?>" class="nav__mobile-link"><i class="fas fa-heart ni"></i>Contribuer</a>
			<div class="nav__mobile-sep"></div>
			<?php if ($this->wowauth->isLogged()): ?>
				<div class="nav__mobile-link" style="cursor: default; justify-content: center; background: rgba(180, 200, 220, .05); border: 1px solid var(--border-lo); margin-bottom: 0.5rem;">
					<i class="dp-icon" style="width:22px; height:22px;"></i>
					<span style="color:var(--text-hi); font-weight:bold; margin-right: 1.5rem;"><?= $this->wowgeneral->getCharDPTotal($this->session->userdata('wow_sess_id')); ?></span>
					
					<i class="vp-icon" style="width:22px; height:22px;"></i>
					<span style="color:var(--text-hi); font-weight:bold;"><?= $this->wowgeneral->getCharVPTotal($this->session->userdata('wow_sess_id')); ?></span>
				</div>

				<a href="<?= base_url('panel'); ?>" class="nav__mobile-link"><i class="fas fa-tachometer-alt ni"></i>Mon compte</a>
				<a href="<?= base_url('logout'); ?>" class="nav__mobile-link" style="color:#ff4d4d"><i class="fas fa-sign-out-alt ni" style="color:#ff4d4d"></i>Déconnexion</a>
			<?php else: ?>
				<a href="<?= base_url('login'); ?>" class="nav__mobile-link"><i class="fas fa-sign-in-alt ni"></i>Connexion</a>
				<?php if ($this->wowmodule->getRegisterStatus() == '1'): ?>
					<a href="<?= base_url('register'); ?>" class="nav__mobile-link" style="color:var(--ice)"><i class="fas fa-user-plus ni" style="color:var(--ice)"></i>S'inscrire</a>
				<?php endif; ?>
			<?php endif; ?>
		</nav>
	</div>



	<?php $_activated_user = $this->session->flashdata('account_just_activated'); ?>
	<?php if ($_activated_user): ?>
	<div id="modal-activated" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
		<div style="background:#1a1d2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:40px 48px;max-width:480px;width:90%;text-align:center;position:relative">
			<div style="font-size:3rem;margin-bottom:16px">✅</div>
			<h2 style="margin:0 0 10px;font-size:1.5rem;color:#fff">Compte activé !</h2>
			<p style="margin:0 0 24px;color:rgba(255,255,255,.7);line-height:1.6">
				Bienvenue <strong style="color:#fff"><?= htmlspecialchars($_activated_user, ENT_QUOTES) ?></strong> sur Celestia-WoW !<br>
				Ton email a bien été confirmé. Tu peux maintenant te connecter au jeu.
			</p>
			<button onclick="document.getElementById('modal-activated').style.display='none'"
				    style="background:linear-gradient(135deg,#7b4ee8,#5b8dee);border:none;color:#fff;padding:12px 32px;border-radius:8px;font-size:1rem;cursor:pointer;width:100%">
				Commencer l'aventure
			</button>
		</div>
	</div>
	<?php endif; ?>
	<main id="cw-main" role="main">
		<?= $template['body']; ?>
	</main>


	<footer class="site-footer" role="contentinfo">
		
        <div class="site-footer__social">
			<span class="site-footer__social-label">Suivez <?= $this->config->item('website_name'); ?></span>
			<div class="site-footer__social-links">
				<a href="<?= $this->config->item('social_youtube'); ?>" target="_blank" class="site-footer__social-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
				<a href="https://discord.gg/JtEgbuy5NU" target="_blank" class="site-footer__social-btn" aria-label="Discord"><i class="fab fa-discord"></i></a>
			</div>
		</div>

		<div class="site-footer__body">
			<a href="<?= base_url(); ?>" class="site-footer__logo">
				<img src="<?= $template['location'] . 'assets/images/logo.png'; ?>" alt="<?= $this->config->item('website_name'); ?>">
				<div class="nav__logo-text site-footer__badge">
					<span class="nav__logo-tagline">Serveur privé</span>
				</div>
			</a>
			<ul class="site-footer__links">
				<li><a href="https://discord.gg/JtEgbuy5NU" class="site-footer__link">Discord</a></li>
				<li><a href="https://celestia-wow.com/bugtracker" class="site-footer__link">Support</a></li>
				<li><a href="https://celestia-wow.com/vote" class="site-footer__link">Vote</a></li>
				<li><a href="<?= base_url('forum'); ?>" class="site-footer__link">Forum</a></li>
				<li><a href="<?= base_url('store'); ?>" class="site-footer__link">Boutique</a></li>
				<li><a href="<?= base_url('donate'); ?>" class="site-footer__link">Contribuer</a></li>
			</ul>
			<a href="https://azerothcore.org/" target="_blank" class="site-footer__badge">
				<img src="https://www.chromiecraft.com/wp-content/uploads/2020/12/AzerothCore.png" alt="AzerothCore">
				<span>Basé sur AzerothCore</span>
			</a>
		</div>

		<div class="site-footer__bottom">
			<div class="site-footer__bottom-wrap">
				<p class="site-footer__copy">
					&copy; <?= date('Y'); ?> <strong><?= $this->config->item('website_name'); ?></strong>.
					<?= $this->lang->line('footer_rights'); ?>
				</p>
				<p class="site-footer__legal">
					World of Warcraft® et Blizzard Entertainment® sont des marques déposées de Blizzard Entertainment.<br>
					Ce site n'est pas associé ni approuvé par Blizzard Entertainment®.
				</p>
			</div>
		</div>
	</footer>


	<script src="<?= $template['assets'] . 'core/js/navbar.js'; ?>"></script>
	<script src="<?= $template['assets'] . 'core/js/runtime.15ccb5b3ab6d70be0680.js'; ?>"></script>
	<script src="<?= $template['assets'] . 'core/js/vendor.f85485fed4e8af52dc96.js'; ?>"></script>
	<script src="<?= $template['assets'] . 'core/js/3.3f71f790ca75774820bf.js'; ?>"></script>
	<script src="<?= $template['assets'] . 'core/js/legacy-components.ee66e0dd5257fdd82677.js'; ?>"></script>

	<script>
		(function() {
			var nav = document.getElementById('cwNav');
			var burger = document.getElementById('cwBurger');
			var mobile = document.getElementById('cwMobile');
			var open = false;

			function onScroll() {
				nav.classList.toggle('is-scrolled', window.scrollY > 30)
			}
			window.addEventListener('scroll', onScroll, { passive: true });
			onScroll();

			function toggle(force) {
				open = force !== undefined ? force : !open;
				burger.classList.toggle('open', open);
				mobile.classList.toggle('open', open);
				burger.setAttribute('aria-expanded', open);
				mobile.setAttribute('aria-hidden', !open);
				document.body.style.overflow = open ? 'hidden' : '';
			}
			burger.addEventListener('click', function() { toggle() });
			mobile.querySelectorAll('.nav__mobile-link').forEach(function(a) {
				a.addEventListener('click', function() { toggle(false) });
			});
			document.addEventListener('click', function(e) {
				if (open && !mobile.contains(e.target) && !burger.contains(e.target)) toggle(false);
			});
			document.addEventListener('keydown', function(e) {
				if (open && e.key === 'Escape') toggle(false);
			});

			var path = window.location.pathname;
			document.querySelectorAll('.nav__link, .nav__mobile-link').forEach(function(a) {
				try {
					var href = a.getAttribute('href');
					if (href && href !== '#' && path === new URL(href, location.origin).pathname) a.classList.add('active');
				} catch (e) {}
			});
		})();
	</script>

</body>

</html>