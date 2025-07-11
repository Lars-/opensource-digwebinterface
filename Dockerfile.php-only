FROM php:8.3-fpm-alpine

# Install required packages
RUN apk add --no-cache \
    bind-tools \
    && docker-php-ext-install -j$(nproc) \
    opcache

# Configure PHP
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Create cache directory with proper permissions
RUN mkdir -p cache && \
    chown -R www-data:www-data cache && \
    chmod 755 cache

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]