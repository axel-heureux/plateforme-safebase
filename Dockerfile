FROM php:8.3-apache

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

WORKDIR /var/www/html
COPY . /var/www/html/

EXPOSE 80
