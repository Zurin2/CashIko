# CashIko — Laravel 13 + Inertia + Vue on Render.
#
# Two stages so the Node toolchain never ships to production:
#   1. assets  — build the Vue bundle with Vite
#   2. app     — PHP-FPM + nginx (serversideup/php), production deps only
#
# PHP is pinned to 8.3 because that is the version the test suite runs against.
# Bump to 8.4-fpm-nginx after running `php artisan test` on 8.4 locally —
# PHP 8.3 leaves security support in December 2026.

# ---------------------------------------------------------------- assets
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# The Vite build reads vite.config.js, postcss.config.js and tailwind.config.js,
# so copy the project rather than guessing which files it needs.
COPY . .
RUN npm run build

# ---------------------------------------------------------------- app
FROM serversideup/php:8.3-fpm-nginx AS app

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false

WORKDIR /var/www/html

# Dependencies first, so a source-only change does not reinstall Composer.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --prefer-dist \
      --no-interaction

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# post-autoload-dump runs package:discover, which writes bootstrap/cache.
RUN composer dump-autoload --optimize --no-dev --no-interaction

EXPOSE 8080

USER www-data
