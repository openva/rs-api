#!/bin/bash

# If the site doesn't already exist, then this is a fresh server.
SITE_SET_UP="$(sudo apache2ctl -S |grep -c api.richmondsunlight.com)"
if [ "$SITE_SET_UP" -eq "0" ]; then

    # Set the timezone to Eastern
    sudo cp /usr/share/zoneinfo/US/Eastern /etc/localtime
    
    # Add swap space, if it doesn't exist
    if [ "$(grep -c swap /etc/fstab)" -eq "0" ]; then
        sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
        sudo /sbin/mkswap /var/swap.1
        sudo chmod 600 /var/swap.1
        sudo /sbin/swapon /var/swap.1
        echo "/var/swap.1   swap    swap    defaults        0   0" | sudo tee /etc/fstab
    fi

    # Ensure the modern PHP repository is available and remove any old PHP packages.
    if ! dpkg -s php8.3-cli >/dev/null 2>&1; then
        INSTALLED_PHP_PACKAGES="$(dpkg -l | awk '/^ii\s+php/{print $2}')"
        if [ -n "$INSTALLED_PHP_PACKAGES" ]; then
            sudo apt-get -y purge $INSTALLED_PHP_PACKAGES
        fi

        # Add the maintained PHP repository (provides PHP 8.x builds).
        sudo add-apt-repository -y ppa:ondrej/php
    fi

    # Add the Certbot repo
    dpkg -s certbot
    if [ $? -eq 1 ]; then
        sudo add-apt-repository -y ppa:certbot/certbot
    fi

    # Install all packages.
    sudo apt-get update
    sudo DEBIAN_FRONTEND=noninteractive apt-get -y upgrade
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
        apache2 \
        curl \
        git \
        gzip \
        unzip \
        openssl \
        libapache2-mod-php8.3 \
        php8.3 \
        php8.3-cli \
        php8.3-curl \
        php8.3-mbstring \
        php8.3-mysql \
        php8.3-xml \
        php8.3-zip \
        php8.3-intl \
        php8.3-apcu \
        mysql-client \
        python3 \
        python3-pip \
        s3cmd \
        wget \
        awscli \
        certbot \
        python3-certbot-apache

    # Install mod_pagespeed
    dpkg -s mod-pagespeed-beta
    if [ $? -eq 1 ]; then
        wget https://dl-ssl.google.com/dl/linux/direct/mod-pagespeed-beta_current_amd64.deb
        sudo dpkg -i mod-pagespeed-*.deb
        sudo apt-get -f install
        rm mod-pagespeed-*.deb
    fi

    # Install Certbot
    dpkg -s certbot
    if [ $? -eq 1 ]; then
        sudo apt-get install -y ruby
        wget https://aws-codedeploy-us-east-1.s3.amazonaws.com/latest/install
        chmod +x ./install
        sudo ./install auto
        rm install
    fi
    
fi
