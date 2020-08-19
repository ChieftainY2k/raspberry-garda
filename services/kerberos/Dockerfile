FROM resin/rpi-raspbian:stretch@sha256:e4938fd283abc60021efa24f8ebbde9d3b0135849dd1297093b06a18578963b4


MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive


RUN echo "deb http://mirrordirector.raspbian.org/raspbian/ stretch main contrib non-free rpi" | tee --append /etc/apt/sources.list
RUN apt-get update && apt-get -y upgrade && apt-get install -y \
    libjpeg62-turbo-dev cmake git mc joe multitail make cmake make \
    sudo g++ libav-tools libssl-dev wget curl \
    mosquitto-clients rpi-update cron rsyslog tmpreaper strace \
    nginx php7.0 php7.0-curl php7.0-gd php7.0-fpm php7.0-cli php7.0-opcache php7.0-mbstring php7.0-xml php7.0-zip php7.0-mcrypt


# fake vcgencmd needed for rpi-update (vcgencmd does not work inside the docker build context)
#ADD ./resources/vcgencmd /bin/vcgencmd
#RUN chmod a+x /bin/vcgencmd

#Install VideoCore libraries etc.
#RUN yes | rpi-update
#RUN cp -r /opt/vc/lib/* /usr/lib/

#Install MACHINERY
ARG RASPBERRY_PLATFORM_FOR_KERBEROS="2"
ARG KERBEROS_MACHINERY_VERSION="2.8.0"
RUN echo "RASPBERRY_PLATFORM_FOR_KERBEROS = ${RASPBERRY_PLATFORM_FOR_KERBEROS} , KERBEROS_MACHINERY_VERSION  = ${KERBEROS_MACHINERY_VERSION}"
RUN wget https://github.com/kerberos-io/machinery/releases/download/v${KERBEROS_MACHINERY_VERSION}/rpi${RASPBERRY_PLATFORM_FOR_KERBEROS}-machinery-kerberosio-armhf-${KERBEROS_MACHINERY_VERSION}.deb -O /tmp/kerberos.deb
RUN dpkg -i /tmp/kerberos.deb

RUN if [ "${KERBEROS_MACHINERY_VERSION}" = "2.8.0" ] ; then wget https://github.com/kerberos-io/machinery/releases/download/v${KERBEROS_MACHINERY_VERSION}/rpi${RASPBERRY_PLATFORM_FOR_KERBEROS}-libx264.so.148 -O /usr/lib/arm-linux-gnueabihf/libx264.so.148 ; fi
RUN if [ "${KERBEROS_MACHINERY_VERSION}" = "2.8.0" ] ; then wget https://github.com/kerberos-io/machinery/releases/download/v${KERBEROS_MACHINERY_VERSION}/rpi${RASPBERRY_PLATFORM_FOR_KERBEROS}-libx265.so.160 -O /usr/lib/arm-linux-gnueabihf/libx265.so.160 ; fi

#Install WEB
RUN rm -f /etc/nginx/sites-enabled/default
ADD ./resources/nginx-kerberosio.conf /etc/nginx/sites-enabled/kerberosio.conf
RUN mkdir -p /var/www/web \
    && chown www-data:www-data /var/www/web

USER www-data
RUN cd /var/www/web \
    && wget https://github.com/kerberos-io/web/releases/download/v2.8.0/web.tar.gz \
    && tar xvf web.tar.gz . \
    && rm -f xvf web.tar.gz


USER root

# Modify kerberos paths...
ADD ./resources/modify-kerberos-links.sh /modify-kerberos-links.sh
RUN chmod a+x /modify-kerberos-links.sh && /modify-kerberos-links.sh

RUN cd /var/www/web \
    && chown www-data -R storage bootstrap/cache config/kerberos.php \
    && chmod -R 775 storage bootstrap/cache \
    && chmod 0600 config/kerberos.php

RUN mkdir /run/php \
    && touch /run/php/php7.0-fpm.sock \
    && chmod a+rwx /run/php/php7.0-fpm.sock

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

# Ngrok tunnel for SSH inside container:
# @TODO remove/disable after tests:
#RUN apt-get update && apt-get install -y openssh-server unzip net-tools
#RUN mkdir /var/run/sshd
#RUN echo 'root:root' | chpasswd
#RUN sed -i 's/PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config
#RUN sed -i 's/#PermitRootLogin yes/PermitRootLogin yes/' /etc/ssh/sshd_config
#RUN sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
#ENV NOTVISIBLE "in users profile"
#RUN echo "export VISIBLE=now" >> /etc/profile
#RUN wget https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-arm.zip && unzip ./ngrok-stable-linux-arm.zip && mv ngrok /sbin/ngrok

# Run inside running container:
# /usr/sbin/sshd && /sbin/ngrok authtoken $NGROK_AUTH_TOKEN && ngrok tcp 22
#
