# sync_website.ps1
# Détecte automatiquement les fichiers website/ modifiés depuis le dernier commit
# et les synchronise vers le VPS en prod via tar + SSH.
#
# Usage :
#   .\sync_website.ps1                        # sync seul
#   .\sync_website.ps1 -DryRun               # aperçu sans rien toucher
#   .\sync_website.ps1 -Commit "description" # sync + commit git + push GitHub
#
# Prérequis : .secrets.ps1 rempli (copier secrets.example.ps1)

param(
    [switch]$DryRun,
    [string]$Commit = ""
)

$ErrorActionPreference = "Stop"
$REPO_ROOT = $PSScriptRoot

# ── Chargement des secrets ────────────────────────────────────────────────────
$secretsFile = Join-Path $REPO_ROOT ".secrets.ps1"
if (-not (Test-Path $secretsFile)) {
    Write-Error "Fichier manquant : .secrets.ps1 -- copier secrets.example.ps1 et remplir les valeurs."
    exit 1
}
. $secretsFile
# Variables attendues : $WEB_USER  $WEB_PASS  $WEB_HOST  $WEB_PATH

# ── Helpers ───────────────────────────────────────────────────────────────────
function Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function OK($msg)   { Write-Host "    OK: $msg" -ForegroundColor Green }
function Info($msg) { Write-Host "    $msg" }
function Warn($msg) { Write-Host "    $msg" -ForegroundColor Yellow }

# ── Détection des changements git ─────────────────────────────────────────────
Step "Détection des changements dans website/..."

# Fichiers modifiés ou ajoutés (staged + unstaged vs HEAD)
$modified  = git -C $REPO_ROOT diff --name-only HEAD -- website/ 2>&1 |
             Where-Object { $_ -match '^website/' }

# Fichiers nouveaux non trackés
$untracked = git -C $REPO_ROOT ls-files --others --exclude-standard -- website/ 2>&1 |
             Where-Object { $_ -match '^website/' }

# Fichiers supprimés
$deleted   = git -C $REPO_ROOT diff --name-only --diff-filter=D HEAD -- website/ 2>&1 |
             Where-Object { $_ -match '^website/' }

$toUpload = @($modified) + @($untracked) | Where-Object { $_ -ne '' -and $null -ne $_ }
$toDelete = @($deleted)                   | Where-Object { $_ -ne '' -and $null -ne $_ }

# ── Résumé ────────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "  Cible      : ${WEB_USER}@${WEB_HOST}:${WEB_PATH}" -ForegroundColor White
Write-Host "  A uploader : $($toUpload.Count) fichier(s)" -ForegroundColor $(if ($toUpload.Count -gt 0) { 'Green' } else { 'Gray' })
Write-Host "  A supprimer: $($toDelete.Count) fichier(s)" -ForegroundColor $(if ($toDelete.Count -gt 0) { 'Red'   } else { 'Gray' })

if ($toUpload.Count -gt 0) {
    Write-Host ""
    $toUpload | ForEach-Object { Write-Host "  + $_" -ForegroundColor Green }
}
if ($toDelete.Count -gt 0) {
    Write-Host ""
    $toDelete | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
}

if ($toUpload.Count -eq 0 -and $toDelete.Count -eq 0) {
    Warn "Aucun changement détecté. Rien à synchroniser."
    exit 0
}

if ($DryRun) {
    Write-Host "`n[DryRun] Simulation uniquement — aucune modification effectuée.`n" -ForegroundColor Yellow
    exit 0
}

# ── Upload des fichiers modifiés via tar ──────────────────────────────────────
if ($toUpload.Count -gt 0) {
    Step "Création de l'archive tar ($($toUpload.Count) fichiers)..."

    $tempTar  = [System.IO.Path]::GetTempFileName() -replace '\.tmp$', '.tar'
    $listFile = [System.IO.Path]::GetTempFileName()

    # Écrire la liste de fichiers (chemins relatifs depuis la racine du repo)
    $toUpload | Set-Content $listFile -Encoding UTF8

    # Créer l'archive (chemins relatifs = website/application/...)
    & tar -cf $tempTar -C $REPO_ROOT --files-from=$listFile 2>&1
    if (-not $?) { Remove-Item $tempTar, $listFile -ErrorAction SilentlyContinue; exit 1 }

    $sizeMB = [math]::Round((Get-Item $tempTar).Length / 1MB, 2)
    OK "Archive créée : $sizeMB MB"

    Step "Upload vers ${WEB_HOST}..."
    & pscp -pw $WEB_PASS -batch $tempTar "${WEB_USER}@${WEB_HOST}:/tmp/celestia_sync.tar" 2>&1
    if (-not $?) { Remove-Item $tempTar, $listFile -ErrorAction SilentlyContinue; exit 1 }
    OK "Upload terminé"

    Step "Extraction sur le serveur (${WEB_PATH})..."
    # --strip-components=1 supprime le préfixe "website/" du tar
    $sshCmd = "tar xf /tmp/celestia_sync.tar -C ${WEB_PATH} --strip-components=1 && rm /tmp/celestia_sync.tar && echo EXTRACT_OK"
    $result = & plink -pw $WEB_PASS -batch "${WEB_USER}@${WEB_HOST}" $sshCmd 2>&1
    if ($result -notcontains "EXTRACT_OK") {
        Write-Error "Erreur lors de l'extraction : $result"
        exit 1
    }
    OK "Fichiers déployés sur le serveur"

    Remove-Item $tempTar, $listFile -ErrorAction SilentlyContinue
}

# ── Suppression des fichiers retirés ──────────────────────────────────────────
if ($toDelete.Count -gt 0) {
    Step "Suppression des $($toDelete.Count) fichier(s) retirés sur le serveur..."
    foreach ($f in $toDelete) {
        $remotePath = $WEB_PATH + '/' + ($f -replace '^website/', '')
        $result = & plink -pw $WEB_PASS -batch "${WEB_USER}@${WEB_HOST}" "rm -f '$remotePath' && echo DEL_OK" 2>&1
        if ($result -contains "DEL_OK") { OK "Supprimé : $remotePath" }
        else { Warn "Attention : $remotePath ($result)" }
    }
}

# ── Commit + push git (optionnel) ─────────────────────────────────────────────
if ($Commit -ne "") {
    Step "Commit git..."
    git -C $REPO_ROOT add -A -- website/
    git -C $REPO_ROOT commit -m $Commit 2>&1
    OK "Commit créé"

    Step "Push vers GitHub..."
    git -C $REPO_ROOT push origin master 2>&1
    OK "Pushé sur GitHub"
}
else {
    Warn "Pas de commit git (ajouter -Commit 'message' pour committer automatiquement)"
}

Write-Host "`nOK Synchronisation terminée.`n" -ForegroundColor Green
