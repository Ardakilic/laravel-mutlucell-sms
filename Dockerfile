FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    libxml2-dev \
    unzip \
    git \
  && docker-php-ext-install simplexml \
  && pecl install pcov \
  && docker-php-ext-enable pcov \
  && rm -rf /var/lib/apt/lists/*

ENV PCOV_ENABLED=1
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
