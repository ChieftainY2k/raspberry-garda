#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][run]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors_warning()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, moving on..."
    fi
}

#check for errors
check_errors_sleep()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, going to sleep..."
        sleep infinity
    fi
}

log_message "starting the health reporter service..."

# fix permissions
chmod u+x /code/garda-health-reporter.sh
check_errors_warning $?

# fix permissions
chmod u+x /code/container-healthcheck.sh
check_errors_warning $?

#wait for external service
until nc -z -w30 mqtt-server 1883
do
    log_message "waiting for the mqtt server to be accessible... "
    sleep 10
done

while sleep 15; do

    log_message "executing the health reporter script..."
    /code/garda-health-reporter.sh
    check_errors_warning $?
    sleep 120

done

#sleep infinity
