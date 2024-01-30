FROM serversideup/php:8.2-fpm-nginx

COPY . /var/www/html
WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
RUN chmod -Rf 777 /var/www/html

RUN composer install

EXPOSE 8001
CMD php artisan serve --host=0.0.0.0 --port=8001