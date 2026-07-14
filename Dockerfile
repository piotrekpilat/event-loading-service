FROM php:8.4-cli-alpine

# Install git, unzip for composer
RUN apk add --no-cache git unzip

# Install redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first to leverage Docker cache
COPY app/composer.json app/composer.lock ./
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application
COPY app/ ./
RUN composer dump-autoload --optimize
