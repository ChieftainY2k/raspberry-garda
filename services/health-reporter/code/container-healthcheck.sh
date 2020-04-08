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

log_message "checking seconds since last successful run..."
secondsSinceLastSuccess=$(expr $(date +%s) - $(stat -c %Y /tmp/health-reporter-success.flag))
check_errors $?

log_message "secondsSinceLastSuccess = ${secondsSinceLastSuccess}"

if [[ "${secondsSinceLastSuccess}" -gt 600 ]]; then
    log_message "last successful run was later than expected."
    exit 1
fi
