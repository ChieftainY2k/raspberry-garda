#!/bin/bash

export COMPOSE_HTTP_TIMEOUT=3600
export COMPOSE_PARALLEL_LIMIT=200
BASEDIR=$( dirname $( readlink -f ${BASH_SOURCE[0]} ) )
DOCKER_COMPOSE="/usr/local/bin/docker-compose"
DOCKER_PARAMS="-f docker-compose.yml -p garda"

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][$(basename $0)]"
    MESSAGE=$1
    echo -e "$LOGPREFIX $MESSAGE"
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
    $0 check     - check workspace and hardware sanity
    $0 benchmark - run benchmarks for filesystem etc.
    $0 status  - show current status of containers and applications
    $0 cleanup - clean all unnecessary data (usused images, containers, files etc.)

    $0 watchdog install         - install garda watchdog checks in the host cron table
    $0 watchdog installhardware - install hardware watchdog
    $0 watchdog check           - run garda watchdog checks

    $0 start   <sevice>  - start container(s)
    $0 stop    <sevice>  - stop containers(s)
    $0 restart <sevice>  - restart containers(s)

    $0 build   <sevice>      - build containers(s) image
    $0 rebuild <sevice>      - stop + remove + rebuild containers(s) image
    $0 rebuildstart <sevice> - stop + remove + rebuild + start containers(s) image

    $0 log <sevice> - show and track container(s) logs

    $0 shell <service>         - launch bash shell console for container
    $0 exec <sevice> <command> - execute a command inside service container

    $0 kerberos log - show and track application logs inside kerberos container

    " | sed "s/^[ \t]*//"
}

get_raspberry_hardware()
{
    local raspberryHardware=$(tr -d '\0' < /proc/device-tree/model)
    echo ${raspberryHardware};
}

get_raspberry_hardware_major_version()
{
    raspberryHardware=$(get_raspberry_hardware)
    if [[ "$raspberryHardware" =~ "Raspberry Pi 4" ]]; then
        echo "4"
    elif [[ "$raspberryHardware" =~ "Raspberry Pi 3" ]]; then
        echo "3"
    elif [[ "$raspberryHardware" =~ "Raspberry Pi 2" ]]; then
        echo "2"
    else
        echo "1"
    fi
}

get_raspberry_version_for_kerberos_build()
{
    local raspberryHardwareMajorVersion=$(get_raspberry_hardware_major_version)
    if [[ "$raspberryHardwareMajorVersion" = "4" ]]; then
        echo "4"
    elif [[ "$raspberryHardwareMajorVersion" = "3" ]]; then
        echo "3"
    else
        echo "2"
    fi
}


get_available_disk_space()
{
    availableDiskSpaceKb=$(df / | grep /dev/root | awk '{print $4/1}')
    echo "${availableDiskSpaceKb}"
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
#    log_message "Raspberry version for kerberos: $(get_raspberry_version_for_kerberos_build)"
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
    log_message "Hardware: $(get_raspberry_hardware)"
    log_message "Kernel: $(uname -a)"
    log_message "OS: $(cat /etc/os-release | grep PRETTY_NAME)"
    log_message "IP address: $(get_ip_address)"

    log_message "Probing for available disk space..."
    pydf | grep -v overlay
    check_errors $?

    log_message "Checking configs/services.conf..."
    stat ./configs/services.conf > /dev/null
    check_errors $?

    #load vars
    . ./configs/services.conf

    #@FIXME move this check to the service ?
    if [[ "${KD_KERBEROS_ENABLED}" == "1" ]]; then
        log_message "Checking if camera module is enabled and taking sample picture (if you get errors here make sure your camera is not used by any other application)..."
        timeout 30 raspistill -o /tmp/$(date +%s).jpg
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

    log_message "Checking docker-compose version..."
    docker-compose -v
    check_errors $?

    log_message "all checks completed."

}


benchmark()
{
    log_message "Installing packages..."
    apt-get -y install hdparm
    check_errors $?

    log_message "Benchmarking the filesystem WRITE performance with DD..."
    sync && dd if=/dev/zero of=/tmp/test.tmp bs=8k count=50k conv=fsync
    check_errors $?

    log_message "Benchmarking the filesystem READ performance with DD..."
    sync && echo 3 > /proc/sys/vm/drop_caches
    check_errors $?

    sync && dd if=/tmp/test.tmp of=/dev/null bs=8k count=50k
    check_errors $?

    log_message "Benchmarking the filesystem performance with HDPARM..."
    sudo hdparm -Tt /dev/mmcblk0
    check_errors $?

    log_message "removing temporary files..."
    rm -f /tmp/test.tmp
    check_errors $?

}


stop()
{
    local SERVICE=${1}

    log_message "Stopping services containers..."
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} stop ${SERVICE}
    check_errors $?
}

start()
{
    local SERVICE=${1}

    log_message "Starting up services containers..."
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} up -d --remove-orphans ${SERVICE}
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
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} rm -f ${SERVICE}
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

    local RASPBERRY_HARDWARE_MAJOR_VERSION=$(get_raspberry_hardware_major_version)
    log_message "Building images..."
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} build \
        --build-arg RASPBERRY_HARDWARE_MAJOR_VERSION=${RASPBERRY_HARDWARE_MAJOR_VERSION} \
        ${SERVICE}
    check_errors $?
}

execute()
{
    local SERVICE=${1}
    local COMMAND=${2}

    log_message "Executing command..."
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} exec ${SERVICE} ${COMMAND}
    check_errors $?
}

log()
{
    local SERVICE=${1}
    log_message "Tracking container logs. Press Ctrl-C to stop."
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} logs -f --tail=40 ${SERVICE}
}

status()
{
    log_message "Hardware: $(get_raspberry_hardware)"
    log_message "Kernel: $(uname -a)"
    log_message "OS: $(cat /etc/os-release | grep PRETTY_NAME)"
    log_message "Raspberry hardware major version: $(get_raspberry_hardware_major_version)"
    log_message "Raspberry version for kerberos: $(get_raspberry_version_for_kerberos_build)"
    log_message "IP address: $(get_ip_address)"

    log_message "Probing for available disk space..."
    pydf | grep -v overlay
    check_errors $?

    log_message "Probing for container status..."
    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} ps

    log_message "checking cron for garda watchdog..."
    crontab -l | grep watchdog

    log_message "checking hardware watchdog service..."
    dmesg | grep -i watchdog
}

shell()
{
    local SERVICE=${1}

    ${DOCKER_COMPOSE} ${DOCKER_PARAMS} exec ${SERVICE} bash
}

kerberos()
{
    local ARG1=${1}

    case ${ARG1} in
        log)
            log_message "Accessing logs in the container..."
            ${DOCKER_COMPOSE} ${DOCKER_PARAMS} exec kerberos bash -c "tail -f /var/log/nginx/* /var/www/web/storage/logs/*"
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
            echo "*/30 * * * * /usr/sbin/tmpreaper -v 30d ${BASEDIR}/logs/watchdog/ > /dev/null ; cd ${BASEDIR} && /usr/bin/flock -w 0 /tmp/garda-watchdog.lock ${BASEDIR}/$(basename ${0}) watchdog run 2>&1 >> ${BASEDIR}/logs/watchdog/watchdog.\$(date \"+\\%Y\\%m\\%d\").log" >> /tmp/garda-crontab.txt
            cat /tmp/garda-crontab.txt | crontab
            check_errors $?
            log_message "checking crontab..."
            crontab -l
            check_errors $?
            log_message "OK, cron table successfully updated, run 'crontab -l' to check it out."
            ;;
        installhardware)

            log_message "backing up /boot/config.txt..."
            cp /boot/config.txt /boot/config.txt.$(date '+%Y%m%d%H%M%S')
            check_errors $?
            log_message "removing old configuration..."
            grep -v "dtparam=watchdog=on" /boot/config.txt > /tmp/bootconfig.txt
            check_errors $?
            log_message "updating configuration..."
            echo "dtparam=watchdog=on" >> /tmp/bootconfig.txt
            check_errors $?
            log_message "replacing config file..."
            cp -f /tmp/bootconfig.txt /boot/config.txt
            check_errors $?

            log_message "backing up /etc/systemd/system.conf..."
            cp /etc/systemd/system.conf /etc/systemd/system.conf.$(date '+%Y%m%d%H%M%S')
            check_errors $?
            log_message "removing old configuration (1)..."
            egrep -v "RuntimeWatchdogSec|ShutdownWatchdogSec" /etc/systemd/system.conf > /tmp/systemconf.txt
            check_errors $?
            log_message "updating configuration (1)..."
            echo "RuntimeWatchdogSec=10" >> /tmp/systemconf.txt
            check_errors $?
            log_message "updating configuration (1)..."
            echo "ShutdownWatchdogSec=10min" >> /tmp/systemconf.txt
            check_errors $?
            log_message "replacing config file..."
            cp -f /tmp/systemconf.txt /etc/systemd/system.conf
            check_errors $?

            log_message "OK, hardware watchdog successfully installed, reboot to activate it."
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
#                    /sbin/shutdown -r now "Rebooting on network loss."
#                    sleep 60
                    systemctl --force --force reboot
                fi
            fi

            log_message "checking containers..."
            dockerPsOutput=$(timeout 900 ${DOCKER_COMPOSE} ${DOCKER_PARAMS} ps)
            EXITCODE=$?
            log_message "docker output: \n${dockerPsOutput}"
            if [[ ${EXITCODE} -ne 0 ]]; then
                log_message "cannot get containers list, docker returned a nonzero exit code (${EXITCODE}), rebooting..."
#                /sbin/shutdown -r now "rebooting because docker ps returned nonzero status"
#                sleep 120
                systemctl --force --force reboot
            else
                log_message "OK, got containers list"
            fi

            unhealthyContainersCount=$(echo ${dockerPsOutput} | grep unhealthy | wc -l)
            if [[ "${unhealthyContainersCount}" != "0" ]]
            then
                #@TODO make a recovery attempt by restarting all containers...
                log_message "there are ${unhealthyContainersCount} unhealthy containers, rebooting..."
                sleep 5
                /sbin/shutdown -r now "rebooting because there are unhealthy containers"
                sleep 120
                systemctl --force --force reboot
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
    benchmark) benchmark;;
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

