FROM php:5.6.39-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite

RUN apt-get update
RUN apt-get install -y git

WORKDIR /var/www/html/

RUN mkdir includes
RUN git clone https://github.com/openva/richmondsunlight.com.git
RUN mv richmondsunlight.com/htdocs/includes includes/
RUN rm -Rf richmondsunlight.com/
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
