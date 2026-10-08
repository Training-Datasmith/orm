FROM php:7.2-cli@sha256:42ffbc0798e4449bbd1e14fc4dcb87774aa1ad1900a09ef6a965bc0880aa2161

RUN sed -i 's|deb.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i '/buster-updates/d' /etc/apt/sources.list

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libsqlite3-dev locales \
    && docker-php-ext-install pdo_sqlite mbstring \
    && sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
    && sed -i 's/# de_DE.UTF-8 UTF-8/de_DE.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && EXPECTED_SIGNATURE="$(curl -sS https://composer.github.io/installer.sig)" \
    && ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")" \
    && test "$EXPECTED_SIGNATURE" = "$ACTUAL_SIGNATURE" \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=2.2.25 \
    && rm /tmp/composer-setup.php

WORKDIR /app
