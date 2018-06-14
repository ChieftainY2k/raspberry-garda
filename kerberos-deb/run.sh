service php7.0-fpm restart
service nginx restart

# Fix permissions
chmod -R 777 /etc/opt/kerberosio/config

# Init cron
cron &

# Init machinery
while sleep 3; do
    echo "Starting kerberos-io machinery..."
    kerberosio
done

#sleep infinity
