# syntax=docker/dockerfile:1

# ---- Stage 1: build frontend assets (Tailwind CSS) ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY tailwind.config.js ./
COPY assets ./assets
COPY app ./app
COPY www ./www
RUN npm run build

# ---- Stage 2: PHP runtime ----
FROM php:8.5-apache AS runtime

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
	&& install-php-extensions pdo_mysql mbstring intl gd zip curl opcache \
	&& a2enmod rewrite \
	&& a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# App source
COPY . .
RUN rm -rf docker
COPY --from=assets /app/www/css ./www/css

RUN mkdir -p temp log www/uploads \
	&& chown -R www-data:www-data temp log www/uploads

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]
