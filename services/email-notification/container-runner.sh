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

send_test_email()
{
    # prepare JSON message
    messageJson=$(cat <<EOF
    {
        "recipients":["${KD_EMAIL_NOTIFICATION_RECIPIENT}"],
        "subject":"${KD_SYSTEM_NAME} email-notification service started",
        "htmlBody":"<b>${KD_SYSTEM_NAME}</b>: <b>email-notification</b> service started at local time <b>${localTime}</b>",
        "attachments":[]
    }
EOF
    )
    messageJson=$(echo ${messageJson} | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')
    log_message "Creating new email in queue , content = ${messageJson}"
    echo "${messageJson}" > /mydata/email-queues/default/health-reporter-startup.json

}

log_message "starting service..."

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

# fix permissions
chmod u+x /code/*.sh
check_errors $?

if [[ "${KD_EMAIL_NOTIFICATION_ENABLED}" != "1" ]]; then
    log_message "NOTICE: service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

# send test email on service start
send_test_email

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
    sleep 15
done

# run  the listener forever
while sleep 1; do

    log_message "starting the MQTT topics collector."
    php -f /code/topic-collector.php
    check_errors_warning $?

    log_message "MQTT topics collector terminated, restarting..."
    sleep 60

done
