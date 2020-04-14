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
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, going to sleep..."
        sleep infinity
    fi
}

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

# fix permissions
chmod u+x /code/container-healthcheck.sh
check_errors $?

if [[ "${KD_KERBEROS_ENABLED}" != "1" ]]; then
    log_message "NOTICE: KERBEROS service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

log_message "starting the kerberos service..."

# replace kerberos web config with a link to the dockerized one
check_errors $?
log_message "backing up config file..."
mv /var/www/web/config/kerberos.php /var/www/web/config/kerberos.php.orig
check_errors $?
log_message "replacing config file..."
ln -s /web-config-dist/kerberos.php /var/www/web/config/kerberos.php
check_errors $?
log_message "initializing configuration dir..."
chmod -R 777 /etc/opt/kerberosio/config
check_errors $?
log_message "initializing session dir..."
chmod -R 777 /var/www/web/storage/framework/sessions
check_errors $?
log_message "initializing configuration file..."
chmod a+rw /var/www/web/config/kerberos.php
check_errors $?

#sleep infinity

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


# Init container health reporter flags
touch /tmp/health-reporter-success.flag
check_errors_warning $?
touch /tmp/autoremove-success.flag
check_errors_warning $?


# Init crontab and cron process
log_message "starting syslog..."
rsyslogd &
check_errors $?
log_message "starting cron..."
cron &
check_errors $?

# Init machinery
while sleep 1; do
    log_message "starting kerberos-io machinery..."
    kerberosio
    log_message "kerberosio process terminated, sleeping and retrying..."
    sleep 60
done

#sleep infinity
