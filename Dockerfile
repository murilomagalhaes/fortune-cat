# Stage 1: Build Node assets
FROM node:22-alpine AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources/ resources/
COPY public/ public/

RUN npm run build

# Stage 2: Install PHP dependencies
FROM composer:2 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Stage 3: Production image
FROM dunglas/frankenphp

RUN install-php-extensions \
    bcmath \
    pdo_mysql \
    opcache \
    intl

COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

COPY --from=composer-build /app /app
COPY --from=node-build /app/public/build public/build/

RUN mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production
ENV LOG_CHANNEL=stderr

EXPOSE 80 443 443/udp