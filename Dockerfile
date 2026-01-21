FROM php:7.4-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

WORKDIR /var/www

# Copy source
COPY . .

# Install PHP dependencies at build time
RUN composer install --no-dev --optimize-autoloader

# Permission
RUN chmod +x start.sh

EXPOSE 10000

CMD ["sh", "start.sh"]
