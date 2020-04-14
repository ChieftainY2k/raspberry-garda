FROM resin/rpi-raspbian:stretch

ARG RASPBERRY_PLATFORM_FOR_KERBEROS=2

MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

RUN echo "RASPBERRY_PLATFORM_FOR_KERBEROS = ${RASPBERRY_PLATFORM_FOR_KERBEROS}"

RUN echo "deb http://mirrordirector.raspbian.org/raspbian/ stretch main contrib non-free rpi" | tee --append /etc/apt/sources.list
RUN apt-get update && apt-get -y upgrade && apt-get install -y \
    libjpeg62-turbo-dev cmake git mc joe multitail make cmake make \
    sudo g++ libav-tools libssl-dev wget curl \
    mosquitto-clients rpi-update cron rsyslog tmpreaper strace

#Install VideoCore libraries etc.

RUN echo "y" | rpi-update
RUN cp -r /opt/vc/lib/* /usr/lib/

#Install MACHINERY

RUN wget https://github.com/kerberos-io/machinery/releases/download/v2.6.1/rpi${RASPBERRY_PLATFORM_FOR_KERBEROS}-machinery-kerberosio-armhf-2.6.1.deb -O /tmp/kerberos.deb
RUN dpkg -i /tmp/kerberos.deb

#Install WEB

RUN apt-get update && apt-get install -y -t stretch \
    nginx php7.0 php7.0-curl php7.0-gd php7.0-fpm php7.0-cli php7.0-opcache php7.0-mbstring php7.0-xml php7.0-zip php7.0-mcrypt
RUN rm -f /etc/nginx/sites-enabled/default
ADD ./resources/nginx-kerberosio.conf /etc/nginx/sites-enabled/kerberosio.conf
RUN mkdir -p /var/www/web && chown www-data:www-data /var/www/web

USER www-data
RUN cd /var/www/web && wget https://github.com/kerberos-io/web/releases/download/v2.5.1/web.tar.gz
RUN cd /var/www/web && tar xvf web.tar.gz .
RUN cd /var/www/web && rm -f xvf web.tar.gz

USER root
RUN cd /var/www/web && chown www-data -R storage bootstrap/cache config/kerberos.php
RUN cd /var/www/web && chmod -R 775 storage bootstrap/cache
RUN cd /var/www/web && chmod 0600 config/kerberos.php
RUN mkdir /run/php
RUN touch /run/php/php7.0-fpm.sock
RUN chmod a+rwx /run/php/php7.0-fpm.sock

# Copy kerberos initial config

# RUN mkdir -p /etc/opt/kerberosio/config
# RUN ADD ./resources/kerberos-configs /etc/opt/kerberosio/config

# Install mqtt reporter

# Configure cron
ADD ./resources/crontab.txt /crontab.txt
RUN crontab /crontab.txt

# Startup scripts
ADD ./container-runner.sh /container-runner.sh
RUN chmod a+x /container-runner.sh
CMD /container-runner.sh

HEALTHCHECK --start-period=10m --interval=5m --timeout=1m \
    CMD /code/container-healthcheck.sh

EXPOSE 80
EXPOSE 8889
