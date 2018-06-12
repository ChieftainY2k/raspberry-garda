service php7.0-fpm restart
service nginx restart

#service kerberosio restart

# Fix permissions
chmod -R 777 /etc/opt/kerberosio/config

while sleep 3; do
    echo "Starting kerberos-io mechinery..."
    kerberosio
done

#sleep infinity
