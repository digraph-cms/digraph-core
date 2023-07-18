FROM ubuntu:22.04
RUN apt update

# prepare to install php 8.2
RUN apt install -y software-properties-common
RUN add-apt-repository ppa:ondrej/php
RUN apt update

# install php 8.2 and other fundamental packages
RUN export DEBIAN_FRONTEND=noninteractive; apt install -y --no-install-recommends php8.2 php-curl composer git openssl unzip

# install the PHP extensions that basically all PHP projects should need
RUN export DEBIAN_FRONTEND=noninteractive; apt install -y php8.2-opcache php-xdebug php-mbstring php-zip php-gd php-xml

# install extensions that are more project-specific
RUN export DEBIAN_FRONTEND=noninteractive; apt install -y php-sqlite3
