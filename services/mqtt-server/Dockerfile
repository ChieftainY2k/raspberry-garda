FROM resin/rpi-raspbian:stretch

MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

# Common stuff

RUN apt-get update && apt-get -y upgrade && apt-get install -y \
    mc joe multitail curl wget telnet psmisc

# Install mqtt server

RUN apt-get install -y mosquitto mosquitto-clients
#add custom config
ADD ./resources/mqtt-server.conf /etc/mosquitto/conf.d/docker.conf

ADD ./container-runner.sh /container-runner.sh
RUN chmod a+x /container-runner.sh
CMD /container-runner.sh

HEALTHCHECK --start-period=10m --interval=5m --timeout=1m \
    CMD /code/container-healthcheck.sh

EXPOSE 1883
