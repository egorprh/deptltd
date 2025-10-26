FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Настройка PHP для загрузки файлов
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Копирование файлов проекта
COPY . /var/www/html

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www/html/data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/data /var/www/html/uploads

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
