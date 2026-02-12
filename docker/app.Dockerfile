FROM php:8.4-fpm

USER root

WORKDIR /var/www

ARG WWWUSER=1000
ARG WWWGROUP=1000

# install system dependencies
RUN apt-get update && apt-get install -y \
  bash \
  git \
  curl \
  unzip \
  zip \
  tzdata \
  libzip-dev \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  libxml2-dev \
  libicu-dev \
  build-essential \
  autoconf \
  make \
  g++ \
  iputils-ping \
  libpq-dev \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# install gosu to drop privileges safely
RUN apt-get update && apt-get install -y gosu && rm -rf /var/lib/apt/lists/*

# install php extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install \
    pdo_pgsql \
    gd \
    zip \
    bcmath \
    opcache \
    sockets \
    pcntl \
    exif \
    intl

# install redis extension
RUN pecl install redis \
  && docker-php-ext-enable redis

# install xdebug and configure
RUN pecl channel-update pecl.php.net \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.client_port=9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.log_level=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# install composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# copy source
ADD . /var/www
