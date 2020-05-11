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

log_message "starting service..."

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

# fix permissions
log_message "fixing permissions..."
chmod u+x /code/container-healthcheck.sh
check_errors $?

# set global UI password
log_message "setting up password..."
htpasswd -cb /etc/nginx/.htpasswd "${KD_UI_USER}" "${KD_UI_PASSWORD}"
check_errors $?

log_message "setting up log redirection (1)..."
ln -sf /proc/1/fd/1 /var/log/nginx/access.log
check_errors $?
log_message "setting up log redirection (2)..."
ln -sf /proc/1/fd/2 /var/log/nginx/error.log
check_errors $?

while sleep 1; do
    log_message "starting nginx..."
    /usr/sbin/nginx
    log_message "nginx finished, sleeping and retrying..."
    sleep 15
done

sleep infinity

