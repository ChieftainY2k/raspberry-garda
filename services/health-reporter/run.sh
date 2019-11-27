#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][health-reporter]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details."
        exit 1
    fi
}

log_message "starting the health reporter service..."

# fix permissions
chmod u+x /code/health-reporter.sh
check_errors $?

#wait for external service
until nc -z -w30 mqtt-server 1883
do
    log_message "waiting for the mqtt server to be accessible... "
    sleep 10
done

while sleep 10; do

    log_message "executing the health reporter script..."
    /code/health-reporter.sh
    check_errors $?
    sleep 120

done

#sleep infinity
