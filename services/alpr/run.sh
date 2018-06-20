
# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

sleep infinity
