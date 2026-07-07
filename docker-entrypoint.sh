#!/bin/sh
set -e

# Render fournit le port via $PORT : Apache doit ecouter dessus.
: "${PORT:=80}"
sed -ri "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Rechauffe le cache de prod avec les vraies variables injectees par Render.
php bin/console cache:clear --env=prod --no-debug || true

# --- Initialisation de la base au premier demarrage (Shell indisponible en Free) ---
# On teste si la table "product" existe deja. Si la requete echoue, c'est que le
# schema n'existe pas encore -> on cree le schema + on charge les fixtures.
# La base n'est donc initialisee qu'UNE seule fois (pas a chaque redemarrage).
if php bin/console doctrine:query:sql "SELECT 1 FROM product LIMIT 1" >/dev/null 2>&1; then
    echo ">>> Base deja initialisee, on ne touche a rien."
else
    echo ">>> Initialisation de la base de donnees..."
    php bin/console doctrine:schema:create --no-interaction || true
    APP_ENV=dev php bin/console doctrine:fixtures:load --no-interaction || true
    echo ">>> Base initialisee."
fi

exec apache2-foreground
