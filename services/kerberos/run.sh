
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

echo "Starting kerberos-io service."

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
while sleep 3; do
    echo "Starting kerberos-io machinery."
    kerberosio
    echo "Kerberos-io process terminated."
done

#sleep infinity
