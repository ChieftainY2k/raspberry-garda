FROM resin/rpi-raspbian:stretch@sha256:e4938fd283abc60021efa24f8ebbde9d3b0135849dd1297093b06a18578963b4

MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

RUN echo "deb http://mirrordirector.raspbian.org/raspbian/ stretch main contrib non-free rpi" | tee --append /etc/apt/sources.list
RUN apt-get update && apt-get -y upgrade && apt-get install -y \
    git mc joe multitail wget curl \
    php7.0-cli php-zip composer telnet mosquitto-clients \
    git libmosquitto-dev mosquitto-dev php-pear php-dev libcurl3-openssl-dev \
    make apt-utils

# Enable mosquitto lib in PHP

RUN echo "" | pecl install Mosquitto-alpha
RUN echo "extension=mosquitto.so" > /etc/php/7.0/mods-available/mosquitto.ini
RUN echo "extension=mosquitto.so" > /etc/php/7.0/cli/conf.d/20-mosquitto.ini

# Add startup script
ADD ./container-runner.sh /container-runner.sh
RUN chmod a+x /container-runner.sh
CMD /container-runner.sh

HEALTHCHECK --start-period=10m --interval=5m --timeout=1m \
    CMD /code/container-healthcheck.sh

EXPOSE 80
