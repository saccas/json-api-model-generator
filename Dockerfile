FROM php:8.3
WORKDIR /app
ENTRYPOINT ["php", "/app/application.php"]

ADD ./app /app
