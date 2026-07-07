#!/bin/sh
set -e

# Render fournit le port via $PORT : Apache doit écouter dessus.
: "${PORT:=80}"
sed -ri "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Réchauffe le cache de prod avec les vraies variables d'environnement injectées par Render.
php bin/console cache:clear --env=prod --no-debug || true

exec apache2-foreground
