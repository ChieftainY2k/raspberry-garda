
while sleep 3; do
    echo "Starting the webhook events listener..."
    php -S 0.0.0.0:8080 /router.php
done


#sleep infinity
