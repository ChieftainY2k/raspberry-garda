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

log_message "starting."

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

if [[ "${KD_KERBEROS_ENABLED}" != "1" ]]; then
    log_message "service is DISABLED, skipping container healthcheck"
    exit 0
fi

log_message "checking http server..."
#curl --silent --fail http://localhost > /dev/null
curl --silent http://localhost > /dev/null
check_errors $?

#check the health of the kerberos stream
log_message "checking video stream..."
ffmpegOutput=$(ffprobe http://localhost:8889 2>&1)
check_errors $?

log_message "checking seconds since last successful service health reporter run..."
secondsSinceLastSuccess=$(expr $(date +%s) - $(stat -c %Y /tmp/health-reporter-success.flag))
check_errors $?
log_message "secondsSinceLastSuccess = ${secondsSinceLastSuccess}"
if [[ "${secondsSinceLastSuccess}" -gt 1200 ]]; then
    log_message "last successful run was later than expected."
    exit 1
fi

log_message "checking seconds since last successful autoremove run..."
secondsSinceLastSuccess=$(expr $(date +%s) - $(stat -c %Y /tmp/autoremove-success.flag))
check_errors $?
log_message "secondsSinceLastSuccess = ${secondsSinceLastSuccess}"
if [[ "${secondsSinceLastSuccess}" -gt 1200 ]]; then
    log_message "last successful run was later than expected."
    exit 1
fi
