
touch /listener.log

while sleep 3; do
    echo "Starting the webhook events listener..."
    php -S 0.0.0.0:8080 /listener.php
done


#sleep infinity
