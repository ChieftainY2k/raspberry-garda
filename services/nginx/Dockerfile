FROM resin/rpi-raspbian:stretch

MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get -y upgrade && apt-get install -y \
    apt-utils git mc joe multitail wget curl telnet git nginx apache2-utils psmisc

ADD ./container-runner.sh /container-runner.sh
RUN chmod a+x /container-runner.sh
CMD /container-runner.sh

COPY resources/nginx.conf /etc/nginx/nginx.conf

HEALTHCHECK --start-period=10m --interval=5m --timeout=1m \
    CMD /code/container-healthcheck.sh

EXPOSE 80

