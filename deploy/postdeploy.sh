#!/bin/bash

# Set permissions properly, since appspec.yml gets this wrong.
chown -R ubuntu:ubuntu /var/www/api.richmondsunlight.com/
chmod -R g+w /var/www/api.richmondsunlight.com/

# Set up Apache, if need be.
SITE_SET_UP="$(sudo apache2ctl -S 2>&1 |grep -c api.richmondsunlight.com)"
if [ "$SITE_SET_UP" -eq "0" ]; then

    # Set up Apache
    sudo cp virtualhost.txt /etc/apache2/sites-available/api.richmondsunlight.com.conf
    sudo a2ensite api.richmondsunlight.com
    sudo a2enmod headers expires rewrite http2
    sudo systemctl reload apache2

    # Install a certificate
    sudo certbot --apache -d api.richmondsunlight.com --non-interactive --agree-tos --email jaquith@gmail.com --redirect

fi
