# HiNoida on Railway — PHP 8.2 + Apache
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install intl mysqli gd zip \
 && a2enmod rewrite headers \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
 && mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar uploads \
 && chown -R www-data:www-data writable uploads

ENV CI_ENVIRONMENT=production
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["sh", "/usr/local/bin/start.sh"]
