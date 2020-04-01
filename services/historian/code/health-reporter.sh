#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][health-reporter]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
    fi
}

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

timestamp=$(date +%s)
localTime=$(date '+%Y-%m-%d %H:%M:%S')
entriesCount=$(/usr/bin/sqlite3 /mydata/mqtt-history.sqlite "select count(*) from mqtt_events")
oldestTimestamp=$(/usr/bin/sqlite3 /mydata/mqtt-history.sqlite "select min(timestamp) from mqtt_events")
databaseFileSizeBytes=$(stat --printf="%s" /mydata/mqtt-history.sqlite)


# prepare JSON message
messageJson=$(cat <<EOF
{
    "timestamp":"${timestamp}",
    "local_time":"${localTime}",
    "history_entries_count":"${entriesCount}",
    "oldest_timestamo":"${oldestTimestamp}",
    "database_file_size":"${databaseFileSizeBytes}"
}
EOF
)

outputfile="/mydata/health-report.json"

log_message "Saving health report to ${outputfile} , content = ${messageJson}"
echo "${messageJson}" > ${outputfile}
check_errors $?
