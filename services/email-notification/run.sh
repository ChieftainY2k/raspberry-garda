#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][email-notification]"
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

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

chmod u+x /code/queue-test-email.sh
check_errors $?

#send test email
/code/queue-test-email.sh
check_errors $?

# Init crontab and cron process
#rsyslogd &
cron &
check_errors $?

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

#wait for external service
until nc -z -w30 mqtt-server 1883
do
    log_message "waiting for the mqtt server to be accessible... "
    sleep 10
done

# run  the listener forever
while sleep 1; do

    echo "Starting the MQTT topics collector."
    php -f /code/topic-collector.php
    check_errors_warning $?

    echo "MQTT topics collector terminated, restarting..."
    sleep 60

done
