#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][alpr]"
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

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment
check_errors $?

# load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

if [[ "${KD_ALPR_ENABLED}" != "1" ]]; then
    log_message "WARNING: ALPR service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi
if [[ "${KD_ALPR_COUNTRY}" != "1" ]]; then
    log_message "WARNING: ALPR country is not configured, going to sleep..."
    sleep infinity
    exit
fi

# Init crontab and cron process
#rsyslogd &
cron &
check_errors $?

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

#sleep infinity

# run  the listener forever
while sleep 10; do
    echo "Starting the MQTT topics collector for ALPR..."
    php -f /code/alpr-topics-collector.php
    echo "MQTT topics collector stopped, sleeping and starting again..."
    sleep 60
done

#echo "MQTT events listener finished."
