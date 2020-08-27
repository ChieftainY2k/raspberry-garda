FROM resin/rpi-raspbian:stretch@sha256:e4938fd283abc60021efa24f8ebbde9d3b0135849dd1297093b06a18578963b4

MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get -y upgrade
RUN apt-get install -y \
    mc joe multitail curl wget unzip \
    php7.0-cli php-zip php-iconv php-fileinfo php-mbstring

# Install code
RUN mkdir /code/
RUN wget https://codeload.github.com/prasathmani/tinyfilemanager/zip/2.4.3 -O /code/filemanager.zip
RUN cd /code/ && unzip ./filemanager.zip && rm -f ./filemanager.zip
COPY resources/config.php /code/tinyfilemanager-2.4.3/config.php

# Startup scripts
ADD ./container-runner.sh /container-runner.sh
RUN chmod a+x /container-runner.sh
CMD /container-runner.sh

#HEALTHCHECK --start-period=10m --interval=5m --timeout=1m \
#    CMD /code/container-healthcheck.sh

