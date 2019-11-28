#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][test-email]"
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

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

localTime=$(date '+%Y-%m-%d %H:%M:%S')

# prepare JSON message
messageJson=$(cat <<EOF
{
    "recipients":["${KD_EMAIL_NOTIFICATION_RECIPIENT}"],
    "subject":"${KD_SYSTEM_NAME} email-notification service started",
    "htmlBody":"<b>${KD_SYSTEM_NAME}</b>: <b>email-notification</b> service started at local time <b>${localTime}</b>",
    "attachments":[]
}
EOF
)

messageJson=$(echo ${messageJson} | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')

log_message "Creating new email in queue , content = ${messageJson}"
echo "${messageJson}" > /data/email-queues/default/health-reporter-startup.json

