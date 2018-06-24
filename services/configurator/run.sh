
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#echo "Starting services..."
#
#service nginx restart
#
## Install external libraries
#echo "Installing libraries..."
#cd /code
#composer install
#
#
#sleep infinity


# init the log file
#touch /listener.log

# run  the listener forever
while sleep 3; do
    echo "Starting the configurator server..."
    php -S 0.0.0.0:80 /code/configurator.php
done


#sleep infinity




