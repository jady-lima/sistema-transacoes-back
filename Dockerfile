FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install \
        bcmath \
        curl \
        dom \
        intl \
        mbstring \
        pdo_pgsql \
        xml \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]