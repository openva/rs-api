FROM mysql:5.6

RUN apt-get update
RUN apt-get install -y git

EXPOSE 3306
CMD ["service mysqld start"]
