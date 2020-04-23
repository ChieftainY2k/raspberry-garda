#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][swarm-watcher]"
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
check_errors $?

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

# fix permissions
chmod u+x /code/container-healthcheck.sh
check_errors $?

if [[ "${KD_SWARM_WATCHER_ENABLED}" != "1" ]]; then
    log_message "NOTICE: Swarm watcher service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

if [[ "${KD_EMAIL_NOTIFICATION_ENABLED}" != "1" ]]; then
    log_message "WARNING: Email notification service required to send messages is DISABLED, email messages will not be sent."
fi

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

# Init crontab and cron process
log_message "starting cron... "
cron 2>&1 > /proc/1/fd/2 &
check_errors $?

# Init web interface
log_message "starting web interface... "
php -S 0.0.0.0:80 /code/web-interface.php 2>&1 > /proc/1/fd/2 &
check_errors $?


#wait for external service
until nc -z -w30 mqtt-server 1883
do
    log_message "waiting for the mqtt server to be accessible... "
    sleep 10
done

# run  the listener forever
while sleep 1; do
    echo "Starting the swarm watcher MQTT topics collector."
    php -f /code/topic-collector.php
    echo "Swarm watcher MQTT topics collector finished with exit code $? , sleeping and starting again..."
    sleep 20
done

