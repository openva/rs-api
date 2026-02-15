FROM php:8-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql && a2enmod rewrite && a2enmod expires && a2enmod headers

RUN printf '<Directory /var/www/html>\n\tAllowOverride All\n</Directory>\n' >> /etc/apache2/apache2.conf

RUN echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/90ignore-release-date \
    && echo 'Acquire::AllowInsecureRepositories "true";' >> /etc/apt/apt.conf.d/90ignore-release-date \
    && echo 'Acquire::AllowDowngradeToInsecureRepositories "true";' >> /etc/apt/apt.conf.d/90ignore-release-date

RUN apt-get update && apt-get install -y git zip zlib1g-dev libmemcached-dev libssl-dev

# Install PHP memcached extension
RUN pecl install memcached && docker-php-ext-enable memcached

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/

COPY . deploy/

WORKDIR /var/www/html/

EXPOSE 80
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
