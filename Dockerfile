FROM php:8.3-apache
ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}
WORKDIR /var/www/html
COPY . /var/www/html/
RUN apt-get update \
 && apt-get install -y --no-install-recommends default-mysql-client postgresql-client libpq-dev \
 && docker-php-ext-install pdo_mysql mysqli \
 && docker-php-ext-install pdo_pgsql \
 && a2enmod rewrite \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && rm -rf /var/lib/apt/lists/*
EXPOSE 80
