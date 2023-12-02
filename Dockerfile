FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /usr/src/php-cfg

RUN git clone https://github.com/ircmaxell/php-cfg.git .

RUN chmod -R 777 /usr/src/php-cfg

RUN composer install

CMD ["/bin/bash"]