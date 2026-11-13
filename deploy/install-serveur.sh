#!/usr/bin/env bash
# Installation / mise à jour de la plateforme JNUAF sur l'hébergement (à lancer en SSH,
# depuis le dossier du site, après avoir extrait jnuaf-test.zip et créé le fichier .env).
#
#   bash deploy-install.sh
#
# Si la commande « php » du terminal n'est pas en 8.2 ou plus, indiquez le bon binaire :
#   PHP=/opt/alt/php83/usr/bin/php bash deploy-install.sh
set -euo pipefail
cd "$(dirname "$0")"

PHP="${PHP:-php}"

echo "== Vérification de PHP ($PHP)"
if ! "$PHP" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'; then
  echo "ERREUR : PHP $("$PHP" -r 'echo PHP_VERSION;') détecté, il faut PHP 8.2 ou plus."
  echo "Cherchez le bon binaire avec :  ls /opt/alt/*/usr/bin/php /opt/cpanel/ea-php*/root/usr/bin/php 2>/dev/null"
  echo "puis relancez :  PHP=/chemin/vers/php bash deploy-install.sh"
  exit 1
fi
"$PHP" -v | head -1

missing=""
for ext in pdo_mysql mbstring gd fileinfo openssl zip intl dom; do
  "$PHP" -m | grep -qi "^$ext$" || missing="$missing $ext"
done
if [ -n "$missing" ]; then
  echo "ERREUR : extensions PHP manquantes :$missing"
  echo "Activez-les dans cPanel > Sélectionner une version de PHP (ou MultiPHP INI Editor)."
  exit 1
fi

if [ ! -f .env ]; then
  echo "ERREUR : fichier .env absent. Copiez deploy/env.test.jnuaf.org en .env et remplissez-le."
  exit 1
fi

echo "== Permissions des dossiers d'écriture"
chmod -R u+rwX,go+rX storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;

if ! grep -q '^APP_KEY=base64:' .env; then
  echo "== Génération de la clé de chiffrement"
  "$PHP" artisan key:generate --force
fi

echo "== Base de données (création des tables + données de départ)"
"$PHP" artisan migrate --force
"$PHP" artisan db:seed --force

echo "== Lien des fichiers publics (photos de la galerie, intervenantes)"
[ -e public/storage ] || "$PHP" artisan storage:link

echo "== Mise en cache pour la production"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo
echo "Installation terminée."
echo "Tâche cron à créer dans cPanel > Tâches Cron (toutes les minutes) :"
echo "  cd $(pwd) && $PHP artisan schedule:run >> /dev/null 2>&1"
