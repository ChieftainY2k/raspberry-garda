
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

echo "Starting services..."

service nginx restart

# Install external libraries
cd /code
composer install


sleep infinity
