
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

# run the simple PHP process to act as web interface
while sleep 3; do
    echo "Starting the configurator server..."
    php -S 0.0.0.0:80 /code/configurator.php
done


#sleep infinity




