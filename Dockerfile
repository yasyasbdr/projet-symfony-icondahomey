FROM php:8.3-apache

# Extensions PHP nécessaires (Postgres + MySQL + intl + bcmath + zip + opcache)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install intl pdo pdo_pgsql pdo_mysql bcmath zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache : la racine web pointe vers public/, activation de la réécriture d'URL
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

# Routage Symfony : toute URL qui ne correspond pas à un fichier réel -> index.php
RUN printf '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>\n' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

ENV APP_ENV=prod
ENV APP_DEBUG=0

# Dépendances (dev incluses pour pouvoir charger les fixtures au démarrage).
RUN APP_SECRET=build DATABASE_URL="postgresql://u:p@localhost:5432/db?serverVersion=16" \
    composer install --optimize-autoloader --no-interaction --no-progress \
    && chown -R www-data:www-data var

COPY docker-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]
