FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y unzip git \
	&& docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

CMD php -S 0.0.0.0:$PORT -t web
