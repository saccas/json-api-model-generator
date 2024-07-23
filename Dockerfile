FROM composer:2.7.7 AS dependencies

WORKDIR /app
ADD ./app/composer.* /app
RUN composer install

FROM php:8.3
WORKDIR /app
ENTRYPOINT ["php", "/app/application.php"]

COPY --from=dependencies /app/vendor /app/vendor

ADD ./app /app
