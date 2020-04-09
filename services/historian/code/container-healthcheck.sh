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

if [[ "${KD_HISTORIAN_ENABLED}" != "1" ]]; then
    log_message "NOTICE: historian service is DISABLED, skipping container healthcheck"
    exit 0
fi

log_message "checking http server..."
curl --fail http://localhost > /dev/null
check_errors $?
