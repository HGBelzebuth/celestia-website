[Diagnostics.CodeAnalysis.SuppressMessageAttribute('PSUseDeclaredVarsMoreThanAssignments', '',
    Justification='Fichier template — variables consommées par sync_website.ps1')]
param()
# secrets.example.ps1
# ─────────────────────────────────────────────────────────────────────────────
# Copier ce fichier en .secrets.ps1 et remplir toutes les valeurs.
# .secrets.ps1 est gitignored — NE JAMAIS le committer.
# ─────────────────────────────────────────────────────────────────────────────

# ── Serveur web (celestia-wow.com) ────────────────────────────────────────────
$WEB_USER = "root"
$WEB_PASS = "MOT_DE_PASSE_VPS"
$WEB_HOST = "IP_SERVEUR"              # ex: 185.246.86.164
$WEB_PATH = "/var/www/website"
