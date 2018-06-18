

# Install external libraries
cd /code
composer install

#sleep infinity

# run  the listener forever
while sleep 3; do
    echo "Starting the MQTT events forwarder..."
    php -f /code/mqtt-forwarder.php
done



