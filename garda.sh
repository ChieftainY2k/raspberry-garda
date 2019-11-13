#!/bin/bash

export COMPOSE_HTTP_TIMEOUT=200
DOCKER_PARAMS="-f docker-compose.yml -p garda"

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][garda]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: there were some errors, check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        #read
        exit 1
    else
        log_message "OK, operation successfully completed."
    fi
}

helper()
{
    echo "
    Available options:
    ---------------------------------------------------
    $0 install - install and configure core and services
    $0 check   - check workspace sanity
    $0 status  - show current status of containers and applications

    $0 start   - start containers
    $0 stop    - stop containers
    $0 restart - restart containers

    $0 build   - build containers
    $0 rebuild - stop + remove + build containers

    $0 log     - show and track container logs

    " | sed "s/^[ \t]*//"
}


install()
{
    ipAddress=$(ip route get 1 | awk '{print $NF;exit}')
    availableDiskSpaceKb=$(df | grep /dev/root | awk '{print $4/1}')
    raspberryDistro=$(tr -d '\0' < /proc/device-tree/model)
    log_message "Starting installation."
    log_message "IP address: $ipAddress , available disk space: $availableDiskSpaceKb kb., hardware = $raspberryDistro"

    log_message "Updating packages..."
    sudo apt-get update -y
    check_errors $?
    sudo apt-get upgrade -y
    check_errors $?

    log_message "Checking if camera module is enabled and taking sample picture..."
    raspistill -o /tmp/$(date +%s).jpg
    check_errors $?

    # Install some required packages first
    log_message "Installing packages..."
    sudo apt install -y \
         apt-transport-https ca-certificates \
         curl wget telnet gnupg2 software-properties-common \
         git mc multitail htop jnettop python python-pip joe pydf \
         build-essential libssl-dev libffi-dev python-dev
    check_errors $?

    log_message "Installing docker..."
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    check_errors $?
    /tmp/get-docker.sh
    check_errors $?

    # Install Docker Compose from pip
    log_message "Installing docker-compose..."
    pip install docker-compose
    check_errors $?

    log_message "Checking docker installation..."
    sudo docker run hello-world
    check_errors $?

    availableDiskSpaceKb=$(df | grep /dev/root | awk '{print $4/1}')
    log_message "Installation complete"
    log_message "IP address: $ipAddress , available disk space: $availableDiskSpaceKb kb., hardware = $raspberryDistro"

}

stop()
{
    log_message "Stopping services containers..."
    docker-compose ${DOCKER_PARAMS} stop
    check_errors $?
}

start()
{
    log_message "Starting up services containers..."
    docker-compose ${DOCKER_PARAMS} up -d --remove-orphans
    check_errors $?

    cleanup

    status
    log_message "Containers started. Use '$0 log' to see container logs."
}

cleanup()
{
    log_message "Removing unused volumes..."
    docker volume prune -f
    check_errors $?

    log_message "Removing unused images..."
    docker image prune -f
    check_errors $?
}

restart()
{
    stop
    start

}

rebuild()
{
    stop

    log_message "Removing containers..."
    docker-compose ${DOCKER_PARAMS} rm -f
    check_errors $?

    build
}

build()
{
    log_message "Building containers..."
    docker-compose ${DOCKER_PARAMS} build
    check_errors $?
}

log()
{
    log_message "Tracking container logs. Press Ctrl-C to stop."
    docker-compose ${DOCKER_PARAMS} logs -f --tail=40
}

status()
{
    log_message "Probing for container status..."
    docker-compose ${DOCKER_PARAMS} ps
}

ARG1=${1}
ARG2=${2}

case ${ARG1} in
    install) install;;
    start)   start;;
    stop)    stop;;
    restart)    restart;;
    build)   build;;
    rebuild)   rebuild;;
    log)     log;;
    status)  status;;
    cleanup)  cleanup;;
    *) helper;;
esac


