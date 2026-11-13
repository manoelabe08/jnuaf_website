# Fabrique deploy\jnuaf-test.zip : le site prêt à envoyer sur l'hébergement (LWS cPanel).
#   - copie propre du projet (sans .env, données locales, tests, fichiers téléversés)
#   - dépendances de production uniquement (composer --no-dev), autoload optimisé
# Usage, depuis le dossier du projet :  powershell -ExecutionPolicy Bypass -File deploy\build.ps1

$ErrorActionPreference = 'Stop'
$root  = Split-Path -Parent $PSScriptRoot
$build = Join-Path $env:TEMP 'jnuaf-build'
$zip   = Join-Path $PSScriptRoot 'jnuaf-test.zip'

Write-Host "1/4 Copie du projet vers $build"
if (Test-Path $build) { Remove-Item $build -Recurse -Force }

# Chemins complets : robocopy /XD avec un nom seul exclurait aussi resources\views, etc.
$excludeDirs = @(
  'vendor', 'node_modules', '.git', 'tests', 'deploy', '.phpunit.cache',
  'public\storage', 'storage\logs', 'storage\app\private', 'storage\app\public',
  'storage\framework\cache', 'storage\framework\sessions', 'storage\framework\views'
) | ForEach-Object { Join-Path $root $_ }
$excludeFiles = @('.env', '.env.backup', '.env.production', 'database.sqlite', '*.log', '.phpunit.result.cache')

robocopy $root $build /E /NFL /NDL /NJH /NJS /NP /XD $excludeDirs /XF $excludeFiles | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy a échoué (code $LASTEXITCODE)" }

# Dossiers d'exécution vides attendus par Laravel
foreach ($d in 'storage\logs', 'storage\app\private', 'storage\app\public',
               'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views') {
  New-Item -ItemType Directory -Force (Join-Path $build $d) | Out-Null
  Set-Content -Path (Join-Path $build "$d\.gitignore") -Value "*`n!.gitignore" -Encoding ascii
}

# Script d'installation côté serveur et modèle de configuration, à la racine du paquet
Copy-Item (Join-Path $PSScriptRoot 'install-serveur.sh') (Join-Path $build 'deploy-install.sh')
Copy-Item (Join-Path $PSScriptRoot 'env.test.jnuaf.org') (Join-Path $build '.env.test.jnuaf.org')

# Caches générés en local : ils référencent les paquets de développement et des chemins Windows
Get-ChildItem (Join-Path $build 'bootstrap\cache') -Filter *.php | Remove-Item -Force

Write-Host "2/4 Installation des dépendances de production (composer --no-dev)"
composer install --working-dir="$build" --no-dev --optimize-autoloader --no-interaction --no-progress
if ($LASTEXITCODE -ne 0) { throw "composer install a échoué" }

Write-Host "3/4 Vérification"
php "$build\artisan" --version
if ($LASTEXITCODE -ne 0) { throw "Le paquet ne démarre pas" }
Get-ChildItem (Join-Path $build 'bootstrap\cache') -Filter *.php | Remove-Item -Force

Write-Host "4/4 Compression vers $zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
# tar (inclus dans Windows 10/11) produit un zip avec des séparateurs « / » lisibles sous Linux,
# contrairement à Compress-Archive de Windows PowerShell 5.1.
tar -a -c -f "$zip" -C "$build" .
if ($LASTEXITCODE -ne 0) { throw "La compression a échoué" }

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Terminé : $zip ($size Mo)"
