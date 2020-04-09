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

#check for errors
check_errors_warning()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details, moving on..."
    fi
}

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

if [[ "${KD_HISTORIAN_ENABLED}" != "1" ]]; then
    log_message "NOTICE: historian service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

log_message "starting historian service..."

# fix permissions
chmod u+x /code/container-healthcheck.sh
check_errors_warning $?

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?


# Init crontab and cron process
rsyslogd &
check_errors $?
cron &
check_errors $?

# Init container health reporter flags
touch /tmp/health-reporter-success.flag
check_errors_warning $?
touch /tmp/garbage-collector-success.flag
check_errors_warning $?

# Init web interface
log_message "starting web interface... "
php -S 0.0.0.0:80 /code/web-interface.php &
check_errors $?

#wait for external service
until nc -z -w30 mqtt-server 1883
do
    log_message "waiting for the mqtt server to be accessible... "
    sleep 10
done

# run  the listener forever
while sleep 1; do

    log_message "starting the MQTT topics collector."
    php /code/topic-collector.php
    check_errors_warning $?

    log_message "MQTT topics collector terminated, restarting..."
    sleep 60

done

sleep infinity

#@TODO add service health reporter (db size, count of stored entries etc.)
