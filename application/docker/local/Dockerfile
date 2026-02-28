FROM ubuntu:22.04

ARG UID=1000
ARG NODE_VERSION=16
ARG PHP_VERSION="8.4"

WORKDIR /application

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update
RUN apt-get install -y gnupg curl

RUN curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor | tee /etc/apt/trusted.gpg.d/microsoft.gpg >> /dev/null
RUN curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list

RUN curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
        && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list

RUN apt-get update && apt-get install -y software-properties-common

RUN add-apt-repository universe

RUN apt-get update
RUN apt-get install -y \
        gosu \
        ca-certificates \
        zip \
        unzip \
        git \
        supervisor \
        libcap2-bin \
        libpng-dev \
        unixodbc \
        unixodbc-dev \
        libc6 \
        libstdc++6 \
        libkrb5-3 \
        libcurl3-dev \
        debconf \
        gnupg2 \
        libldb-dev \
        libfreetype6-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        openssl \
        libsodium-dev \
        mysql-client \
        cron \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18

RUN apt-get update \
    && apt-get install -y  \
        php$PHP_VERSION-cli  \
        php$PHP_VERSION-dev \
        php$PHP_VERSION-gd \
        php$PHP_VERSION-imagick \
        php$PHP_VERSION-curl \
        php$PHP_VERSION-mysql  \
        php$PHP_VERSION-mbstring \
        php$PHP_VERSION-xml  \
        php$PHP_VERSION-zip  \
        php$PHP_VERSION-bcmath \
        php$PHP_VERSION-intl  \
        php$PHP_VERSION-readline \
        php$PHP_VERSION-redis \
        php$PHP_VERSION-ldap \
        php$PHP_VERSION-fpm \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN apt-get update \
    && apt-get install -y poppler-utils \
    && apt-get install aptitude -y \
    && aptitude install ldap-utils -y \
    && apt-get install php-ldap -y

COPY docker/local/crontab /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron
RUN crontab /etc/cron.d/laravel-cron


RUN setcap "cap_net_bind_service=+ep" /usr/bin/php$PHP_VERSION

RUN useradd -ms /bin/bash -u $UID application

COPY docker/local/start-container /usr/local/bin/start-container
COPY docker/local/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/local/php.ini /etc/php/$PHP_VERSION/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80
EXPOSE 9000

COPY --chown=application:application . .

CMD bash -c "cron && /usr/bin/php -d variables_order=EGPCS && composer install && php artisan serve --host 0.0.0.0 --port 80"
