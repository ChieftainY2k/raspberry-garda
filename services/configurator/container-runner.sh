#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][configurator]"
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
        log_message "WARNING: Exit code ${EXITCODE} , there were some errors - check the ouput for details, moving on..."
    fi
}

log_message "starting service..."

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment
check_errors $?

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

# fix permissions
log_message "fixing permissions..."
chmod u+x /code/container-healthcheck.sh
check_errors_warning $?

# Install external libraries
log_message "installing libraries..."
cd /code
check_errors $?
composer install
check_errors $?

# run the simple PHP process to act as web interface
while sleep 1; do
    log_message "starting the web server..."
    php -S 0.0.0.0:80 /code/configurator.php
    log_message "configurator server stopped, sleeping and starting again..."
    sleep 60
done

#sleep infinity
