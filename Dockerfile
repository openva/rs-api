FROM php:5.6.39-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite

RUN apt-get update
RUN apt-get install -y git


WORKDIR /var/www/html/
COPY . /var/www/html/

RUN git clone https://github.com/openva/richmondsunlight.com.git
RUN mv richmondsunlight.com/htdocs/includes htdocs/includes
RUN rm -Rf richmondsunlight.com/

EXPOSE 80
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
