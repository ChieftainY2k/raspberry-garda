#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][ngrok]"
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

log_message "starting the ngrok service..."

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

if [[ "${KD_NGROK_ENABLED}" != "1" ]]; then
    log_message "NOTICE: NGROK service is DISABLED, going to sleep..."
    sleep infinity
    exit
fi

if [[ "$KD_NGROK_AUTHTOKEN" != "" ]]; then
    log_message "Using authtoken ..."
    /sbin/ngrok authtoken ${KD_NGROK_AUTHTOKEN}
    check_errors $?
else
    log_message "NOTICE: Authtoken is empty, initializing tunnel as anonymous user."
fi

log_message "Starting the ngrok process..."
/sbin/ngrok http -log stdout nginx:80 &
check_errors $?
sleep 10

log_message "NGROK status for all tunnels:"
curl --silent --show-error http://127.0.0.1:4040/api/tunnels
check_errors $?

while sleep 1; do

    NGROK_TUNNEL_URL=$(curl --silent --show-error http://127.0.0.1:4040/api/tunnels | sed -nE 's/.*public_url":"https:..([^"]*).*/\1/p')
    log_message "NGROK tunnel public url is $NGROK_TUNNEL_URL"

    timestamp=$(date +%s)
    localTime=$(date '+%Y-%m-%d %H:%M:%S')

    # prepare JSON message
    messageJson=$(cat <<EOF
{
    "timestamp":"${timestamp}",
    "local_time":"${localTime}",
    "ngrok_url":"${NGROK_TUNNEL_URL}"
}
EOF
)

    #save health report
    reportFile="/mydata/health-report.json"
    messageJson=$(echo ${messageJson} | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')
    log_message "Saving health report to file ${reportFile} , report = ${messageJson}"
    echo "${messageJson}" > ${reportFile}

    sleep 600
done


