#!/bin/bash

cd /var/www/html

# Set the include path in .htaccess if not already present
if [ "$(grep include_path .htaccess 2>/dev/null | grep -v "#" | wc -l | xargs)" -eq 0 ]; then
    echo 'php_value include_path ".:includes/"' >> .htaccess
fi

# Have PHP report errors
if [ "$(grep error_reporting .htaccess 2>/dev/null | grep -v "#" | wc -l | xargs)" -eq 0 ]; then
    echo 'php_value error_reporting 32767' >> .htaccess
fi

# Show errors in the browser for easier debugging inside Docker
if [ "$(grep display_errors .htaccess 2>/dev/null | grep -v "#" | wc -l | xargs)" -eq 0 ]; then
    echo 'php_flag display_errors On' >> .htaccess
fi

# Ensure includes directory exists
if [ ! -d "includes" ]; then
    echo "Warning: includes directory not found"
    exit 1
fi

# Install Composer dependencies if composer.json exists in includes
cd /var/www/html/includes
if [ -f "composer.json" ]; then
    # This keeps Composer from balking at directory permissions
    git config --global --add safe.directory /var/www/html/includes 2>/dev/null || true
    composer install --no-interaction --prefer-dist 2>/dev/null || true
fi

echo "API setup complete"
