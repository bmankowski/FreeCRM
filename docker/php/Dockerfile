FROM php:8.3-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    ghostscript \
    imagemagick \
    poppler-utils \
    libcurl4-openssl-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libc-client2007e-dev \
    libkrb5-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
  && docker-php-ext-install -j"$(nproc)" \
    curl \
    dom \
    exif \
    gd \
    imap \
    mbstring \
    pdo \
    pdo_mysql \
    soap \
    xml \
    zip \
  && rm -rf /var/lib/apt/lists/*

COPY docker/php/entrypoint.sh /usr/local/bin/freecrm-entrypoint
RUN chmod +x /usr/local/bin/freecrm-entrypoint

# PHP ini overrides for installer recommended settings (load after docker-*.ini)
RUN rm -f /usr/local/etc/php/conf.d/99-freecrm.ini
COPY docker/php/conf.d/freecrm.ini /usr/local/etc/php/conf.d/zz-freecrm.ini

# PHP-FPM pool overrides (web context)
COPY docker/php/fpm.d/zz-freecrm.conf /usr/local/etc/php-fpm.d/zz-freecrm.conf

WORKDIR /var/www/html

ENTRYPOINT ["freecrm-entrypoint"]
CMD ["php-fpm"]
