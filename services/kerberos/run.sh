#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][kerberos]"
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

log_message "starting the kerberos ervice..."

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

if [[ "${KD_KERBEROS_ENABLED}" != "1" ]]; then
    log_message "NOTICE: KERBEROS service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

log_message "initializing configuration..."
cp -vnr /resources/kerberos-configs /etc/opt/kerberosio/config
check_errors $?
chmod -R 777 /etc/opt/kerberosio/config
check_errors $?

log_message "starting frontend app..."
service php7.0-fpm restart
check_errors $?
log_message "starting nginx..."
service nginx restart
check_errors $?


# Fix permissions & run the script
chmod u+x /code/autoremoval.sh
check_errors $?
/code/autoremoval.sh
check_errors $?

# Init crontab and cron process
rsyslogd &
check_errors $?
cron &
check_errors $?

# Init machinery
while sleep 60; do
    log_message "starting kerberos-io machinery..."
    kerberosio
    log_message "kerberosio process terminated, sleeping and retrying..."
done

#sleep infinity
