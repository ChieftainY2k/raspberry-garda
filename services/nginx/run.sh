
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

#sleep infinity

# set global UI password
htpasswd -cb /etc/nginx/.htpasswd "${KD_UI_USER}" "${KD_UI_PASSWORD}"

while sleep 3; do
    echo "Starting nginx..."
    /usr/sbin/nginx
done

