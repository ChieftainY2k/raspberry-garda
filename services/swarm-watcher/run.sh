
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# Init crontab and cron process
#rsyslogd &
cron &

# Install external libraries
cd /code
composer install

#sleep infinity

# run  the listener forever
while sleep 10; do
    echo "Starting the swarm watcher MQTT topics collector."
    php -f /code/swarm-watcher-topic-collector.php
done

#echo "MQTT events listener finished."
