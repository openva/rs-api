FROM mysql:5.7

RUN apt-get update
RUN apt-get install -y git

RUN git clone https://github.com/openva/rs-machine.git
RUN mysql < rs-machine/deploy/database.sql
RUN rm -Rf rs-machine

EXPOSE 3306
CMD ["service mysqld start"]
