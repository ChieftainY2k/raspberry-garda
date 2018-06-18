
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# Init crontab and cron process
rsyslogd &
cron &

# Install external libraries
cd /code
composer install

#sleep infinity

# run  the listener forever
while sleep 10; do
    echo "Starting the MQTT events collector."
    php -f /code/mqtt-collector.php
done

#echo "MQTT events listener finished."
