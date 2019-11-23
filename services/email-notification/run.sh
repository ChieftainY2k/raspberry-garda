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
    EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: there were some errors, check the ouput for details."
        exit 1
    fi
}

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# Init crontab and cron process
#rsyslogd &
cron &
check_errors $?

# Install external libraries
cd /code
check_errors $?
composer install
check_errors $?

#sleep infinity

# run  the listener forever
while sleep 10; do

    until nc -z -w30 mqtt-server 1883
    do
        log_message "waiting for the mqtt server to be accessible... "
        sleep 10
    done

    echo "Starting the MQTT topics collector."
    php -f /code/topic-collector.php
    check_errors $?
done

#echo "MQTT events listener finished."
