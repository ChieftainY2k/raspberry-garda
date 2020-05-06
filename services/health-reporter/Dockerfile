FROM resin/rpi-raspbian:stretch

MAINTAINER "ChieftainY2k@gmail.com"

ENV DEBIAN_FRONTEND noninteractive

# Common stuff

RUN apt-get update && apt-get -y upgrade && apt-get install -y \
    mc joe multitail curl wget telnet libraspberrypi-bin bc mosquitto-clients ffmpeg netcat

# Install scripts
#
#ADD ./health-reporter.sh /health-reporter.sh
#RUN chmod a+x /health-reporter.sh

# Startup scripts
ADD ./container-runner.sh /container-runner.sh
RUN chmod a+x /container-runner.sh
CMD /container-runner.sh

HEALTHCHECK --start-period=10m --interval=5m --timeout=1m \
    CMD /code/container-healthcheck.sh


