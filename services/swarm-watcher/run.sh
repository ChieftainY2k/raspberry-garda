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
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
        exit 1
    fi
}

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

# Init crontab and cron process
cron &
check_errors $?

# run  the listener forever
while sleep 10; do
    echo "Starting the swarm watcher MQTT topics collector."
    php -f /code/swarm-watcher-topic-collector.php
    check_errors $?
    echo "Swarm watcher MQTT topics collector finished."
done

