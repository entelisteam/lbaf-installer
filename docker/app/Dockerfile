# Контекст сборки — корень репозитория (см. docker-compose.yml), потому что
# в prod-образ кладется код проекта.
#
# Таргеты: skeleton-dev (код приезжает бинд-маунтом) и skeleton-prod (код и
# vendor внутри образа).

FROM php:8.3-fpm AS skeleton-base

# install-php-extensions сам подтягивает нужные системные пакеты и убирает их за собой
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
# @lbaf-if phinx
# mysqli — для приложения (lbaf-mysql), pdo_mysql — для phinx, opcache — на прод
RUN install-php-extensions mysqli pdo_mysql opcache
# @lbaf-else
# mysqli — для приложения (lbaf-mysql), opcache — на прод
RUN install-php-extensions mysqli opcache
# @lbaf-endif

WORKDIR /app


FROM skeleton-base AS skeleton-dev

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/app/dev.ini "$PHP_INI_DIR/conf.d/zz-skeleton.ini"


# Сборка вендора отдельной стадией: композер и его системные пакеты не попадают
# в финальный образ
FROM skeleton-base AS skeleton-build

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN install-php-extensions zip

# сначала только манифесты: пока lock не менялся, слой с зависимостями берется из кеша
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction


FROM skeleton-base AS skeleton-prod

# php.ini-production: display_errors=Off, log_errors=On
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/app/prod.ini "$PHP_INI_DIR/conf.d/zz-skeleton.ini"

COPY --from=skeleton-build /app /app
