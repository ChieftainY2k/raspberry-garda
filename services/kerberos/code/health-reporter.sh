#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][healthreporter]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: there were some errors, check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        exit 1
    fi
}

#check the health of the kerberos stream
streamFFprobeOutput=$(ffprobe http://localhost:8889 2>&1 | tail -1)
log_message "FFProbe output = ${streamFFprobeOutput}"

timestamp=$(date +%s)
localTime=$(date '+%Y-%m-%d %H:%M:%S')

# prepare JSON message
messageJson=$(cat <<EOF
{
    "timestamp":"${timestamp}",
    "local_time":"${localTime}",
    "video_stream":"${streamFFprobeOutput}"
}
EOF
)

outputfile="/data-services-health-reports-kerberos/report.json"

log_message "Saving health report to ${outputfile} , content = ${messageJson}"
echo "${messageJson}" > ${outputfile}
