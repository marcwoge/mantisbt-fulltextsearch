# MantisBT test image for trying out the Open Ticket Reminder plugin.
FROM php:8.2-apache

ENV MANTIS_VERSION=2.28.3

# PHP extensions required by MantisBT (mysqli, gd, mbstring, zip).
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		curl ca-certificates \
		libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" mysqli gd mbstring zip \
	&& rm -rf /var/lib/apt/lists/*

# Download and unpack the official MantisBT release (bundles PHPMailer/ADOdb).
RUN set -eux; \
	curl -fSL "https://downloads.sourceforge.net/project/mantisbt/mantis-stable/${MANTIS_VERSION}/mantisbt-${MANTIS_VERSION}.tar.gz" -o /tmp/mantis.tar.gz; \
	tar -xzf /tmp/mantis.tar.gz -C /tmp; \
	rm -rf /var/www/html; \
	mv "/tmp/mantisbt-${MANTIS_VERSION}" /var/www/html; \
	chown -R www-data:www-data /var/www/html; \
	rm /tmp/mantis.tar.gz

RUN printf 'date.timezone=Europe/Berlin\nmemory_limit=256M\n' \
	> /usr/local/etc/php/conf.d/mantis.ini

EXPOSE 80
