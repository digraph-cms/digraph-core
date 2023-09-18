FROM ubuntu:22.04

# prepare to install php 8.2
RUN apt update && apt install -y software-properties-common
RUN add-apt-repository ppa:ondrej/php

# install php 8.2 and other fundamental packages
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y --no-install-recommends php8.2 php-curl git openssl unzip

# install composer and its CA certificates
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=composer:latest /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt

# install the PHP extensions that basically all PHP projects should need
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y php8.2-opcache php-xdebug php-mbstring php-zip php-gd php-xml

# install extensions that are more project-specific
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y php8.2-sqlite3 npm

# add cron job to container and start cron
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y cron
RUN crontab -l | { cat; echo "*/2 * * * * /usr/bin/php /workspace/digraph-core/demo/scripts/cron.php"; } | crontab -
RUN service cron restart