#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][historian]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
    fi
}

log_message "starting historian service..."

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

log_message "historian service started."

sleep infinity
