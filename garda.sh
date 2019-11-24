#!/bin/bash

export COMPOSE_HTTP_TIMEOUT=1200
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
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        read
        #exit 1
#    else
#        log_message "OK, operation successfully completed."
    fi
}

helper()
{
    echo "
    Available options:
    ---------------------------------------------------
    $0 install - install and configure core and services
    $0 check   - check workspace and hardware sanity
    $0 status  - show current status of containers and applications

    $0 start   <sevice>  - start container(s)
    $0 stop    <sevice>  - stop containers(s)
    $0 restart <sevice> - restart containers(s)

    $0 build   <sevice> - build containers(s) image
    $0 rebuild <sevice> - stop + remove + rebuild containers(s) image

    $0 log <sevice>     - show and track container(s) logs

    $0 shell <service>  - lauhch bash shell console for container

    $0 kerberos log   - show and track application logs inside kerberos container

    " | sed "s/^[ \t]*//"
}

get_raspberry_hardware()
{
    local raspberryHardware=$(tr -d '\0' < /proc/device-tree/model)
    echo ${raspberryHardware};
}

get_raspberry_version_for_kerberos_build()
{
    local raspberryHardware=$(get_raspberry_hardware)
    if [[ "$raspberryHardware" =~ "Raspberry Pi 4 Model" ]]; then
        echo "3"
    elif [[ "$raspberryHardware" =~ "Raspberry Pi 3 Model" ]]; then
        echo "3"
    else
        echo "2"
    fi
}

get_available_disk_space()
{
    availableDiskSpaceKb=$(df | grep /dev/root | awk '{print $4/1}')
    echo ${availableDiskSpaceKb};
}

get_ip_address()
{
    ipAddress=$(ip route get 1 | awk '{print $NF;exit}')
    echo ${ipAddress}
}


install()
{
    ipAddress=$(get_ip_address)
    availableDiskSpaceKb=$(get_available_disk_space)
    log_message "Starting installation."
    log_message "IP address: $ipAddress"
    log_message "Available disk space: $availableDiskSpaceKb kb."
    log_message "Hardware = $(get_raspberry_hardware)"
    log_message "Raspberry version for kerberos: $(get_raspberry_version_for_kerberos_build)"

    log_message "Updating packages..."
    apt-get update -y
    check_errors $?
    apt-get upgrade -y
    check_errors $?

    # Install some required packages first
    log_message "Installing packages..."
    apt install -y \
         apt-transport-https ca-certificates \
         curl wget telnet gnupg2 software-properties-common \
         git mc multitail htop jnettop python python-pip joe pydf \
         build-essential libssl-dev libffi-dev python-dev
    check_errors $?

    log_message "Installing docker..."
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    check_errors $?
    chmod u+x /tmp/get-docker.sh
    check_errors $?
    /tmp/get-docker.sh
    check_errors $?

    # Install Docker Compose from pip
    log_message "Installing docker-compose..."
    pip install docker-compose
    check_errors $?

    log_message "IP address: $ipAddress"
    log_message "Available disk space: $availableDiskSpaceKb kb."
    log_message "Probing for available disk space..."
    pydf
    check_errors $?

    log_message "Installation completed"
}

check()
{
    log_message "Checking config file..."
    stat ./configs/services.conf
    check_errors $?

    #load vars
    #    export $(grep -v '^#' /configs/services.conf | xargs -d '\n')

    log_message "Checking if camera module is enabled and taking sample picture..."
    raspistill -o /tmp/$(date +%s).jpg
    check_errors $?

    log_message "Checking docker installation..."
    docker run hypriot/armhf-hello-world
    check_errors $?

}

stop()
{
    local SERVICE=${1}

    log_message "Stopping services containers..."
    docker-compose ${DOCKER_PARAMS} stop ${SERVICE}
    check_errors $?
}

start()
{
    local SERVICE=${1}

    log_message "Starting up services containers..."
    docker-compose ${DOCKER_PARAMS} up -d --remove-orphans ${SERVICE}
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
    local SERVICE=${1}
    stop ${SERVICE}
    start ${SERVICE}

}

rebuild()
{
    local SERVICE=${1}

    stop ${SERVICE}

    log_message "Removing containers..."
    docker-compose ${DOCKER_PARAMS} rm -f ${SERVICE}
    check_errors $?

    build ${SERVICE}
}

build()
{
    local SERVICE=${1}

    local RASPBERRY_PLATFORM_FOR_KERBEROS=$(get_raspberry_version_for_kerberos_build)
    log_message "Building images..."
    docker-compose ${DOCKER_PARAMS} build \
        --build-arg RASPBERRY_PLATFORM_FOR_KERBEROS=${RASPBERRY_PLATFORM_FOR_KERBEROS} \
        ${SERVICE}
    check_errors $?
}

log()
{
    local SERVICE=${1}
    log_message "Tracking container logs. Press Ctrl-C to stop."
    docker-compose ${DOCKER_PARAMS} logs -f --tail=40 ${SERVICE}
}

status()
{
    ipAddress=$(get_ip_address)
    raspberryHardware=$(get_raspberry_hardware)
    log_message "Hardware: $raspberryHardware"
    log_message "Raspberry version for kerberos: $(get_raspberry_version_for_kerberos_build)"
    log_message "IP address: $ipAddress"
    log_message "Probing for available disk space..."
    pydf
    log_message "Probing for container status..."
    docker-compose ${DOCKER_PARAMS} ps
}

shell()
{
    local SERVICE=${1}

    docker-compose ${DOCKER_PARAMS} exec ${SERVICE} bash
}

kerberos()
{
    local ARG1=${1}

    case ${ARG1} in
        log)
            log_message "Accessing logs in the container..."
            docker-compose ${DOCKER_PARAMS} exec kerberos bash -c "tail -f /var/log/nginx/* /var/www/web/storage/logs/*"
            ;;
        *)
            helper
            exit 1
            ;;
    esac
}

ARG1=${1}
ARG2=${2}

case ${ARG1} in
    install) install;;
    check) check;;
    start)   start ${ARG2};;
    stop)    stop ${ARG2};;
    restart) restart ${ARG2};;
    build)   build ${ARG2};;
    rebuild) rebuild ${ARG2};;
    log)     log ${ARG2};;
    shell)   shell ${ARG2};;
    status)  status;;
    cleanup) cleanup;;
    kerberos)  kerberos ${ARG2};;
    *)
        helper
        exit 1
        ;;
esac

