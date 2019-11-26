#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][thermometer]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
        exit 1
    fi
}

log_message "starting thermometer service..."

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment


if [[ "${KD_THERMOMETER_ENABLED}" != "1" ]]; then
    log_message "NOTICE: Thermometer service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi


log_message "probing for temperature sensors..."
cat /sys/bus/w1/devices/28*/w1_slave
check_errors $?

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

## run  the listener forever
while sleep 1; do
    php -f /code/thermo-watcher.php
    sleep 30
done

sleep infinity
