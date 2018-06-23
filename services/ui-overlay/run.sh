
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

echo "Starting services..."

service nginx restart

sleep infinity
