
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

sleep infinity

while sleep 3; do
    echo "Starting nginx..."
    /usr/sbin/nginx -g "daemon off;"
done






