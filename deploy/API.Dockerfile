FROM php:5.6.39-apache
RUN docker-php-ext-install mysqli && docker-php-ext-install mysql && a2enmod rewrite && a2enmod expires

RUN apt-get update
RUN apt-get install -y git

WORKDIR /var/www/html/

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
