#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][nginx]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details."
        exit 1
    fi
}

log_message "starting the nginx service..."

# Workaround: preserve the environment for cron process
printenv | grep -v "no_proxy" >> /etc/environment

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

#sleep infinity

# set global UI password
htpasswd -cb /etc/nginx/.htpasswd "${KD_UI_USER}" "${KD_UI_PASSWORD}"

while sleep 1; do
    log_message "starting nginx..."
    /usr/sbin/nginx
    log_message "nginx finished, sleeping and retrying..."
    sleep 15
done

