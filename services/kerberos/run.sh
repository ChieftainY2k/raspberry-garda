#!/bin/bash

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

if [[ "${KD_KERBEROS_ENABLED}" != "1" ]]; then
    echo "NOTICE: kerberos service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

echo "Starting kerberos services..."

service php7.0-fpm restart
service nginx restart

# Fix permissions
chmod -R 777 /etc/opt/kerberosio/config

# Fix permissions & run the script
chmod u+x /code/autoremoval.sh
/code/autoremoval.sh

# Init crontab and cron process
rsyslogd &
cron &

# Init machinery
while sleep 60; do
    echo "Starting kerberos-io machinery."
    kerberosio
    echo "Kerberos-io process terminated."
done

#sleep infinity
