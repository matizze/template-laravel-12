# syntax=docker/dockerfile:1.7
# Multi-stage Laravel Octane APP (production-ready, minimal)

# Stage 1: Composer vendors
ARG PHP_VERSION=8.4
ARG COMPOSER_VERSION=latest
FROM composer:${COMPOSER_VERSION} AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-interaction --prefer-dist \
    --ignore-platform-reqs --optimize-autoloader \
    --apcu-autoloader --ansi --no-scripts

# Stage 2: Gerar assets
FROM node:24-alpine AS assets
WORKDIR /src

COPY package*.json ./

RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Runtime
FROM --platform=linux/amd64 matizzee/php:octane-8.4

ARG APP_USER=app
ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV DEBIAN_FRONTEND=noninteractive \
    TERM=xterm-color \
    CONTAINER_MODE=app \
    OCTANE_SERVER=swoole \
    ROOT=/app \
    PORT=8000

WORKDIR ${ROOT}
SHELL ["/bin/bash", "-euo", "pipefail", "-c"]

# Copy vendors + code
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /src/public/build ./public/build
RUN rm -f ${ROOT}/public/admin.html

# Entrypoint
COPY --chmod=755 deployment/runtime/entrypoint.sh /entrypoint.sh

# User + permissions
RUN groupadd -g ${WWWGROUP} ${APP_USER} \
    && useradd -ms /bin/bash -g ${WWWGROUP} -u ${WWWUSER} ${APP_USER} \
    && mkdir -p storage/{framework/{sessions,views,cache},logs} bootstrap/cache \
    && chown -R ${APP_USER}:${APP_USER} storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

USER ${APP_USER}

WORKDIR ${ROOT}

EXPOSE ${PORT}

ENTRYPOINT ["/entrypoint.sh"]
