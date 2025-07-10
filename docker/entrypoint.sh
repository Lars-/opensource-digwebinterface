#!/bin/sh
set -e

# Ensure cache directory exists with proper permissions
mkdir -p /var/www/html/cache
chown -R www-data:www-data /var/www/html/cache
chmod 755 /var/www/html/cache

# Update dig path in config if needed
DIG_PATH=$(which dig)
if [ -f /var/www/html/config/config.php ]; then
    sed -i "s|'dig_path' => '[^']*'|'dig_path' => '$DIG_PATH'|g" /var/www/html/config/config.php
fi

# Execute the original command
exec "$@"