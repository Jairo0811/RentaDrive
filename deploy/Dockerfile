# syntax=docker/dockerfile:1
#
# Imagen de runtime para staging/producción de RentaDrive (PHP 8.4 FPM + ODBC 18 + pdo_sqlsrv).
# Sin secretos: toda configuración llega por variables de entorno en tiempo de ejecución.
# Contexto de construcción: raíz del repositorio. Ver docs/staging-private.md.

ARG PHP_IMAGE=php:8.4-fpm-bookworm

# ---------- assets (Vite) ----------
FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

# ---------- base PHP con extensiones ----------
FROM ${PHP_IMAGE} AS base
ENV ACCEPT_EULA=Y DEBIAN_FRONTEND=noninteractive
RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl gnupg unixodbc-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
 && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
 && echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends msodbcsql18 \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd intl zip bcmath pcntl opcache \
 && pecl install sqlsrv pdo_sqlsrv \
 && docker-php-ext-enable sqlsrv pdo_sqlsrv \
 && rm -rf /tmp/pear /var/lib/apt/lists/* \
 && php -r 'if (version_compare(PHP_VERSION, "8.4.27", "<")) { fwrite(STDERR, "PHP 8.4.27+ requerido\n"); exit(1); }'

# ---------- dependencias PHP (sin dev) ----------
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

# ---------- runtime ----------
FROM base AS runtime
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY deploy/staging/php.ini /usr/local/etc/php/conf.d/zz-rentadrive.ini
COPY deploy/staging/php-fpm.conf /usr/local/etc/php-fpm.d/zz-rentadrive.conf
RUN rm -rf tests e2e marketing deploy .github node_modules \
 && rm -f .env .env.* playwright.config.js \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R u+rwX,g+rX,o-rwx storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm"]

# ---------- web (nginx sin privilegios con public/ y assets compilados) ----------
FROM nginxinc/nginx-unprivileged:stable-alpine AS web
COPY --from=runtime /var/www/html/public /var/www/html/public
COPY deploy/staging/nginx.conf /etc/nginx/conf.d/default.conf
