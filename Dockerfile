FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
	libfreetype6-dev \
	libjpeg62-turbo-dev \
	libpng-dev \
	libwebp-dev \
	libzip-dev \
	libonig-dev \
	unzip git \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
	&& docker-php-ext-install gd pdo pdo_mysql exif fileinfo

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

CMD php -S 0.0.0.0:${PORT:-8080} -t web web/router.php
