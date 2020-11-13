FROM php:7.4-apache
SHELL ["/bin/bash", "-oeux", "pipefail", "-c"]

# timezone environment
ENV TZ=UTC \
    # locale
    LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8 \
    # composer environment
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

RUN apt-get update && \
    curl -sL https://deb.nodesource.com/setup_14.x | bash - && \
    apt-get -y install git libicu-dev libonig-dev libzip-dev unzip locales nodejs && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    locale-gen en_US.UTF-8 && \
    localedef -f UTF-8 -i en_US en_US.UTF-8 && \
    docker-php-ext-install intl pdo_mysql zip bcmath && \
    composer config -g process-timeout 3600 && \
    composer config -g repos.packagist composer https://packagist.org && \
    npm install npm@latest -g

RUN mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enable
RUN /bin/sh -c a2enmod rewrite

COPY ./apache.d/000-default.conf /etc/apache2/sites-enabled/
COPY ./php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html/laravel
