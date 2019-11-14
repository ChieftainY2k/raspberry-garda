
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# Init crontab and cron process
cron &

# Install external libraries
cd /code
composer install

# run  the listener forever
while sleep 10; do
    echo "Starting the swarm watcher MQTT topics collector."
    php -f /code/swarm-watcher-topic-collector.php
    echo "Swarm watcher MQTT topics collector finished."
done

