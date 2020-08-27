#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][$(basename $0)]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, going to sleep..."
        sleep infinity
    fi
}

#check for errors
check_errors_warning()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, moving on..."
    fi
}

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# fix permissions
#chmod a+x /code/container-healthcheck.sh
#check_errors $?

#if [[ "${KD_HISTORIAN_ENABLED}" != "1" ]]; then
#    log_message "NOTICE: service is DISABLED, going to sleep..."
#    sleep infinity
#    exit
#fi

log_message "starting service..."

# run the simple PHP process to act as web interface
while sleep 1; do
    log_message "starting the web server..."
    php -S 0.0.0.0:80 /code/tinyfilemanager-2.4.3/tinyfilemanager.php
    log_message "server stopped, sleeping and starting again..."
    sleep 60
done


sleep infinity


