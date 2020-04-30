#!/bin/bash

export COMPOSE_HTTP_TIMEOUT=3600
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
#        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details."
#        read
        exit 1
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
    $0 cleanup - clean all unnecessary data (usused images, containers, files etc.)

    $0 watchdog install         - install garda watchdog checks in the host cron table
    $0 watchdog installhardware - install hardware watchdog
    $0 watchdog check           - run garda watchdog checks

    $0 start   <sevice>  - start container(s)
    $0 stop    <sevice>  - stop containers(s)
    $0 restart <sevice> - restart containers(s)

    $0 build   <sevice> - build containers(s) image
    $0 rebuild <sevice> - stop + remove + rebuild containers(s) image
    $0 rebuildstart <sevice> - stop + remove + rebuild + start containers(s) image

    $0 log <sevice>     - show and track container(s) logs

    $0 shell <service>         - launch bash shell console for container
    $0 exec <sevice> <command> - execute a command inside service container

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
        echo "4"
    elif [[ "$raspberryHardware" =~ "Raspberry Pi 3 Model" ]]; then
        echo "3"
    else
        echo "2"
    fi
}

get_available_disk_space()
{
    availableDiskSpaceKb=$(df / | grep /dev/root | awk '{print $4/1}')
    echo ${availableDiskSpaceKb}
}

get_ip_address()
{
#    ipAddress=$(ip route get 1 | awk '{print $NF;exit}')
    ipAddress=$(ip route get 1)
    echo ${ipAddress}
}


install()
{
    log_message "Hardware: $(get_raspberry_hardware)"
    log_message "Kernel: $(uname -a)"
    log_message "OS: $(cat /etc/os-release | grep PRETTY_NAME)"
    log_message "Raspberry version for kerberos: $(get_raspberry_version_for_kerberos_build)"
    log_message "IP address: $(get_ip_address)"

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
         git mc multitail htop jnettop python3 python3-pip joe pydf \
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
    pip3 install docker-compose
    check_errors $?

    log_message "IP address: $ipAddress"
    log_message "Probing for available disk space..."
    pydf
    check_errors $?

    log_message "Installation completed"
}

check()
{
    log_message "Checking configs/services.conf..."
    stat ./configs/services.conf > /dev/null
    check_errors $?

    #load vars
    . ./configs/services.conf


    #@FIXME move this check to the service ?
    if [[ "${KD_KERBEROS_ENABLED}" == "1" ]]; then
        log_message "Checking if camera module is enabled and taking sample picture (if you get errors here make sure your camera is not used by any other application)..."
        raspistill -o /tmp/$(date +%s).jpg
        check_errors $?
    fi

    #@FIXME move this check to the service ?
    if [[ "${KD_THERMOMETER_ENABLED}" == "1" ]]; then
        log_message "Checking sensors' kernel files..."
        ls -la /sys/bus/w1/devices/28*/w1_slave
        log_message "Checking if we can read from 1-wire temperature sensor..."
        cat /sys/bus/w1/devices/28*/w1_slave
        check_errors $?
    fi

    log_message "Checking docker installation, attempting to run a simple container..."
    docker run --rm hypriot/armhf-hello-world
    check_errors $?

    log_message "Checking the filesystem WRITE performance..."
    sync; dd if=/dev/zero of=/tmp/test.tmp bs=500K count=1024
    check_errors $?
    log_message "Checking the filesystem READ performance..."
    sync; echo 3 > /proc/sys/vm/drop_caches
    check_errors $?
    sync; dd if=/tmp/test.tmp of=/dev/null bs=500K count=1024
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

#    cleanup

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

rebuildstart()
{
    local SERVICE=${1}

    rebuild ${SERVICE}
    start ${SERVICE}
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

execute()
{
    local SERVICE=${1}
    local COMMAND=${2}

    log_message "Executing command..."
    docker-compose ${DOCKER_PARAMS} exec ${SERVICE} ${COMMAND}
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
    log_message "Hardware: $(get_raspberry_hardware)"
    log_message "Kernel: $(uname -a)"
    log_message "OS: $(cat /etc/os-release | grep PRETTY_NAME)"
    log_message "Raspberry version for kerberos: $(get_raspberry_version_for_kerberos_build)"
    log_message "IP address: $(get_ip_address)"

    log_message "Probing for available disk space..."
    pydf | grep -v overlay

    log_message "Probing for container status..."
    docker-compose ${DOCKER_PARAMS} ps

    log_message "checking cron for garda watchdog..."
    crontab -l | grep watchdog

    log_message "checking hardware watchdog service..."
    service watchdog status
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

watchdog()
{
    local ARG1=${1}

    case ${ARG1} in
        install)
            log_message "installing packages..."
            apt-get -qy install tmpreaper
            check_errors $?
            log_message "installing cron script for watchdog..."
            crontab -l | grep -v "$(basename ${0}) watchdog run" > /tmp/garda-crontab.txt
            BASEDIR=$( dirname $( readlink -f ${BASH_SOURCE[0]} ) )
            echo "*/30 * * * * /usr/sbin/tmpreaper -v 30d ${BASEDIR}/logs/watchdog/ ; /usr/bin/flock -w 0 /tmp/gardca-watchdog.lock ${BASEDIR}/$(basename ${0}) watchdog run 2>&1 >> ${BASEDIR}/logs/watchdog/watchdog.\$(date \"+\\%Y\\%m\\%d\").log" >> /tmp/garda-crontab.txt
            cat /tmp/garda-crontab.txt | crontab
            check_errors $?
            log_message "checking crontab..."
            crontab -l
            check_errors $?
            log_message "OK, cron table successfully updated, run 'crontab -l' to check it out."
            ;;
        installhardware)
            log_message "installing watchdog kernel module..."
            modprobe bcm2835_wdt
            check_errors $?
            log_message "creating temporary file for modules..."
            cat /etc/modules | grep -v "bcm2835_wdt" > /tmp/modules
            check_errors $?
            log_message "inserting watchdog kernel module into temporary file..."
            echo "bcm2835_wdt" >> /tmp/modules
            check_errors $?
            log_message "backing up /etc/modules..."
            cp /etc/modules /etc/modules.$(date '+%Y%m%d%H%M%S')
            check_errors $?
            log_message "replacing /ect/modules with temporary file..."
            mv /tmp/modules /etc/modules
            check_errors $?
            log_message "installing watchdog package..."
            apt-get -y install watchdog
            check_errors $?
            log_message "configuring rc scripts..."
            update-rc.d watchdog defaults
            check_errors $?
            log_message "backing up /etc/watchdog.conf..."
            cp /etc/watchdog.conf /etc/watchdog.conf.$(date '+%Y%m%d%H%M%S')
            check_errors $?
            log_message "updating /etc/watchdog.conf [1]..."
            echo "watchdog-timeout = 15" >> /etc/watchdog.conf
            check_errors $?
            log_message "updating /etc/watchdog.conf [2]..."
            sed -i "s/^#max-load-1[^1-9].*/max-load-1 = 24/g"  /etc/watchdog.conf
            check_errors $?
            log_message "updating /etc/watchdog.conf [3]..."
            sed -i "s|^#watchdog-device.*|watchdog-device = /dev/watchdog|g"  /etc/watchdog.conf
            check_errors $?
            log_message "starting watchdog service..."
            service watchdog start
            check_errors $?
            log_message "probing watchdog service status..."
            service watchdog status
            check_errors $?
            log_message "OK, hardware watchdog successfully installed"
            ;;
        run)
            log_message "checking internet connection..."
            ping -c5 1.1.1.1
            if [[ $? == 0 ]]
            then
                log_message "OK, network connection is working"
            else
                log_message "WARNING: no network connection!"
                log_message "stopping interfaces..."
                sudo ifconfig wlan0 down
                sudo ifconfig eth0 down
                log_message "starting interfaces..."
                sudo ifconfig wlan0 up
                sudo ifconfig eth0 up
                log_message "waiting for interfaces..."
                sleep 180
                ping -c5 1.1.1.1
                if [[ $? != 0 ]]
                then
                    log_message "CRITICAL: still no network connection, rebooting..."
                    /sbin/shutdown -r now "Rebooting on network loss."
                fi
            fi

            log_message "checking containers..."
            dockerPsOutput=$(docker ps -a)
            if [[ $? != 0 ]]
            then
                log_message "cannot get containers list, 'docker ps' returned a nonzero exit code, rebooting..."
                /sbin/shutdown -r now "rebooting because docker ps returned nonzero status"
            else
                log_message "OK, got containers list"
            fi

            unhealthyContainersCount=$(echo ${dockerPsOutput} | grep unhealthy | wc -l)
            if [[ "${unhealthyContainersCount}" != "0" ]]
            then
                #@TODO make a recovery attempt by restarting all containers...
                log_message "there are ${unhealthyContainersCount} unhealthy containers, rebooting..."
                /sbin/shutdown -r now "rebooting because there are unhealthy containers"
            else
                log_message "OK, there are no unhealthy containers"
            fi

            ;;
        *)
            helper
            exit 1
            ;;
    esac
}

ARG1=${1}
ARG2=${2}
ARG3=${3}

case ${ARG1} in
    install) install;;
    watchdog) watchdog ${ARG2};;
#    watchdog-install) watchdog_install;;
#    watchdog-check) watchdog_check;;
    check) check;;
    start)   start ${ARG2};;
    stop)    stop ${ARG2};;
    restart) restart ${ARG2};;
    build)   build ${ARG2};;
    rebuild) rebuild ${ARG2};;
    rebuildstart) rebuildstart ${ARG2};;
    log)     log ${ARG2};;
    shell)   shell ${ARG2};;
    exec)   execute ${ARG2} ${ARG3};;
    status)  status;;
    cleanup) cleanup;;
    kerberos)  kerberos ${ARG2};;
    *)
        helper
        exit 1
        ;;
esac
