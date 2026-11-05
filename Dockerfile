FROM php:8.2-apache

# متطلبات بناء امتدادات PHP ومتطلبات Laravel وMySQL
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
 && docker-php-ext-install pdo_mysql mbstring zip gd bcmath \
 && rm -rf /var/lib/apt/lists/*

# تثبيت امتدادات gRPC وProtocol Buffers كلٌّ على حدة
RUN pecl channel-update pecl.php.net \
 && pecl install grpc \
 && docker-php-ext-enable grpc

RUN pecl install protobuf \
 && docker-php-ext-enable protobuf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# تشغيل Laravel من مجلد public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && a2enmod rewrite

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
