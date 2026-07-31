# syntax=docker/dockerfile:1

###############################################################################
# STAGE 1 — Composer (build dependencies only, thrown away afterwards)
###############################################################################
FROM composer:2 AS composer

WORKDIR /app

# Never let Composer run out of memory on a small VPS
ENV COMPOSER_MEMORY_LIMIT=-1

# Install dependencies first so this layer is cached until composer files change
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Copy the full source, then run the Yii2 composer scripts
# (setPermission, generateCookieValidationKey, etc.)
COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

###############################################################################
# STAGE 2 — Runtime (minimal, fast, low memory)
###############################################################################
FROM php:8.2-cli AS runtime

WORKDIR /app

# Safety limits:
#   - never spawn parallel compiles (light CPU usage on the server)
#   - keep Composer memory in check
ENV MAKEFLAGS="-j1" \
    IPE_PROCESSOR_COUNT=1 \
    IPE_GD_WITHOUTAVIF=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Precompiled PHP extensions — no manual gcc/make, so no RAM/CPU spikes
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    fileinfo \
    gd \
    zip \
    intl \
    opcache \
    && echo 'opcache.enable_cli=1' > /usr/local/etc/php/conf.d/opcache.ini

# Dependencies from stage 1, then the app source
COPY --from=composer /app/vendor /app/vendor
COPY . .

# Do not carry local development .env into the image (env vars come from Coolify).
# Give the web server (www-data) write access to Yii2 writable folders so
# logs, cache, sessions and uploads work without permission errors.
RUN rm -f .env \
    && mkdir -p \
        runtime \
        web/assets \
        web/uploads \
        storage/uploads \
    && chown -R www-data:www-data \
        runtime \
        web/assets \
        web/uploads \
        storage/uploads \
    && chmod -R 0777 \
        runtime \
        web/assets \
        web/uploads \
        storage/uploads

USER www-data

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "web", "web/router.php"]
