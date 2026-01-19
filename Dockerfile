FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY . .

RUN chmod +x start.sh

EXPOSE 10000

CMD ["sh", "start.sh"]
