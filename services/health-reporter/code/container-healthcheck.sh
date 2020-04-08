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
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, exiting."
        exit 1
    fi
}

log_message "running healthcheck..."
if [[ -f "/tmp/last-health-reporter-failed.flag" ]]; then
    log_message "failure flag is set, setting container as unhealthy."
    exit 1
fi



