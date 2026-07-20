# ---- Dependências (Composer) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# ---- Runtime ----
FROM php:8.3-cli
WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libonig-dev \
        unzip \
    && docker-php-ext-install pdo pdo_pgsql bcmath mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app .

EXPOSE 8080

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host 0.0.0.0 --port ${PORT:-8080}"]
