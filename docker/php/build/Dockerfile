FROM php:8.2-fpm

WORKDIR /var/www

ENV USER=syncio
ENV USER_PASSWORD=syncio
ENV UID=1000

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN apt-get update && apt-get install -y \
	vim \
	git \
	curl \
	libpng-dev \
	libonig-dev \
	libxml2-dev \
	zip \
	unzip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN yes | pecl install xdebug
RUN docker-php-ext-enable xdebug
COPY extensions/Xdebug/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN touch /tmp/xdebug_remote.log
RUN chown www-data:www-data /tmp/xdebug_remote.log
RUN chmod 664 /tmp/xdebug_remote.log

RUN id -u $USER >/dev/null 2>&1 || useradd -G www-data,root -u $UID -d /home/$USER $USER
RUN mkdir -p /home/$USER/.composer && \
	chown -R $USER:$USER /home/$USER
RUN echo "${USER}:${USER_PASSWORD}" | chpasswd
RUN chsh -s /usr/local/bin/bash $USER

USER root
