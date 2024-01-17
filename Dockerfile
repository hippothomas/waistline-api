ARG VERSION="8.3"

FROM php:${VERSION}-apache

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the code into the container
COPY . .

## Copy custom configurations
COPY ./deployment/apache.conf /etc/apache2/sites-available/000-default.conf
COPY ./deployment/php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Install PHP extensions and other dependencies
RUN apt-get update && \
    apt-get install -y wget libpq-dev libicu-dev && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install intl opcache pgsql pdo pdo_pgsql && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite

## Install composer
RUN wget https://getcomposer.org/installer && \
    php installer --install-dir=/usr/local/bin/ --filename=composer && \
    rm installer

## Install application dependencies
ENV APP_ENV=prod
RUN composer install --no-dev --no-interaction --optimize-autoloader

## Change files owner to apache default user
RUN chown -R www-data:www-data /var/www/html

## Cleanup
RUN composer dump-autoload --no-dev --classmap-authoritative && \
    rm /usr/local/bin/composer

# Expose the port Apache listens on
EXPOSE 80
