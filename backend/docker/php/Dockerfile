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

EXPOSE 9000

CMD ["php-fpm"]