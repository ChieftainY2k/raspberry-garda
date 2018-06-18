
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

echo "Starting kerberos-io service."

service php7.0-fpm restart
service nginx restart

# Fix permissions
chmod -R 777 /etc/opt/kerberosio/config

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
