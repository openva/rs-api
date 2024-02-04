FROM php:5-apache

# Replace sources.list with the archived repository URLs
RUN echo "deb http://archive.debian.org/debian/ stretch main non-free contrib" > /etc/apt/sources.list \
    && echo "deb-src http://archive.debian.org/debian/ stretch main non-free contrib" >> /etc/apt/sources.list \
    && echo "deb http://archive.debian.org/debian-security/ stretch/updates main" >> /etc/apt/sources.list \
    && echo "deb-src http://archive.debian.org/debian-security/ stretch/updates main" >> /etc/apt/sources.list

RUN docker-php-ext-install mysqli && docker-php-ext-install mysql && a2enmod rewrite && a2enmod expires && a2enmod headers

RUN apt --fix-broken install
RUN apt-get update
RUN apt-get install -y git zip zlib1g-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/

COPY . deploy/

WORKDIR /var/www/html/

EXPOSE 80
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
