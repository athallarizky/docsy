# Production image for PAAS deploys (Railway) — self-contained:
# code + vendor baked in, no bind mounts.
# Local dev keeps using docker/php/Dockerfile + volume mount via compose.
FROM php:8.3-fpm-alpine

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_pgsql pgsql redis bcmath intl zip pcntl opcache gd

# Raise PHP upload limits for the 25MB file contract
RUN { \
        echo 'upload_max_filesize=30M'; \
        echo 'post_max_size=30M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Composer binary from the official image (multi-stage copy)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# App code + production dependencies baked in
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

# PAAS entrypoint: fix volume ownership, migrate, then run php-fpm with the
# queue worker in the background (single service = single volume for uploads).
CMD sh -c "chown -R www-data:www-data storage bootstrap/cache && php artisan migrate --force && php artisan queue:work redis --sleep=3 --tries=3 --backoff=5,30,120 & exec php-fpm"
