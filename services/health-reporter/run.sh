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
    EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: there were some errors, check the ouput for details."
        exit 1
    fi
}

log_message "starting the health reporter service..."

# fix permissions
chmod u+x /code/health-reporter.sh
check_errors $?

while sleep 10; do

    until nc -z -w30 mqtt-server 1881
    do
        log_message "waiting for the mqtt server to be accessible... "
        sleep 10
    done

    log_message "executing the health reporter script..."
    /code/health-reporter.sh
    check_errors $?
    sleep 100

done

#sleep infinity
