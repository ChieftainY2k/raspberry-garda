
sleep infinity

# run  the listener forever
while sleep 3; do
    echo "Starting the MQTT events listener..."
    php -f /mqtt-listener.php
done


