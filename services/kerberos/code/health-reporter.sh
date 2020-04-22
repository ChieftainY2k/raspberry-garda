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
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        exit 1
    fi
}

#check the health of the kerberos stream
streamFFprobeOutput=$(ffprobe http://localhost:8889 2>&1 | tail -1)
log_message "FFProbe output = ${streamFFprobeOutput}"

timestamp=$(date +%s)
localTime=$(date '+%Y-%m-%d %H:%M:%S')
imageDir=/etc/opt/kerberosio/capture/
filesCount=$(ls -f ${imageDir}| wc -l) # number of captured files

# prepare JSON message
messageJson=$(cat <<EOF
{
    "timestamp":"${timestamp}",
    "local_time":"${localTime}",
    "video_stream":"${streamFFprobeOutput}",
    "media_files_count":"${filesCount}"
}
EOF
)

outputfile="/mydata/health-report.json"

log_message "Saving health report to ${outputfile} , content = ${messageJson}"
echo "${messageJson}" > ${outputfile}

#set success flag for the container health reporter
touch /tmp/health-reporter-success.flag
check_errors $?

