FROM php:8.3-fpm-alpine

# Install required packages including nginx and supervisor
RUN apk add --no-cache \
    nginx \
    supervisor \
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

# Configure PHP-FPM to listen on unix socket for better performance
RUN sed -i 's/listen = 9000/listen = \/var\/run\/php-fpm.sock/' /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "listen.owner = nginx" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "listen.group = nginx" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "listen.mode = 0660" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy nginx configuration
COPY docker/nginx-combined.conf /etc/nginx/nginx.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Create necessary directories with proper permissions
RUN mkdir -p /var/log/supervisor /run/nginx cache && \
    chown -R www-data:www-data /var/www/html/cache && \
    chmod 755 /var/www/html/cache && \
    chown -R nginx:nginx /var/log/nginx /run/nginx

# Expose port 80 (nginx)
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]