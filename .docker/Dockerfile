FROM php:8.3.0-fpm

# Устанавливаем системные зависимости одним слоем
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    libpq-dev \
    libxpm-dev \
    libvpx-dev \
    libzip-dev \
    cron \
    nano \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем PHP расширения
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/ \
    --with-xpm=/usr/lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    zip \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    exif \
    sockets \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-enable pdo_mysql

RUN apt-get update && \
    apt-get install -y jq && \
    rm -rf /var/lib/apt/lists/*


# Создаем пользователя www
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Настройка рабочей директории
WORKDIR /var/www/html

USER www

