#!/bin/bash

#TODO optimize this script

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][autoremove]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
        exit 1
    fi
}


log_message "cleanup started."

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

#LOGPREFIX="$(date '+%Y-%m-%d %H:%M:%S')[autoremove]"
imageDir=/etc/opt/kerberosio/capture/
partition=$(df ${imageDir} | awk '/^\/dev/ {print $1}')


usedPercent=$(df -h | grep ${partition} | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
#spaceTotalKb=$(df --sync $imagedir | tail -1 | awk '{print $1}') # total space (free+used)
spaceUsedKb=$(df --sync ${imageDir} | tail -1 | awk '{print $2}') # used space
spaceAvailableKb=$(df --sync ${imageDir} | tail -1 | awk '{print $4}') # currently available free space on device
filesCount=$(find ${imageDir}| wc -l) # number of captured files
totalFilesSizeKb=$(du ${imageDir} | tail -1 | awk '{print $1}') # total size of captured files
log_message "partition $partition for $imageDir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

#max allowed space for files:
#maximumAllowedSpaceTakenKb=600000 # fixed = how much we allow files to take
#maximumAllowedSpaceTakenKb=$(($spaceAvailableKb-1500000)) # dynamic = related to the overall free space on device
#@TODO make it a variable param with services config
maximumAllowedSpaceTakenKb=$(($spaceAvailableKb-1000000)) # dynamic = related to the overall free space on device
#logMessage "totalFilesSizeKb = $totalFilesSizeKb"
#logMessage "maximumAllowedSpaceTakenKb = $maximumAllowedSpaceTakenKb"
#logMessage "spaceAvailableKb = $spaceAvailableKb"

cleanupPerformed=0
#while [ $totalFilesSizeKb -gt $maximumAllowedSpaceTakenKb ]
if [[ ${spaceAvailableKb} -lt 1000000 ]]; then
    log_message "cleaning up, removing some oldest files in $imageDir ..."
    find ${imageDir} -type f | sort | head -n 100 | xargs -r rm -rf;

    usedPercent=$(df -h | grep ${partition} | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
#    spaceTotalKb=$(df --sync $imagedir | tail -1 | awk '{print $1}') # total space (free+used)
    spaceUsedKb=$(df --sync ${imageDir} | tail -1 | awk '{print $2}') # used space
    spaceAvailableKb=$(df --sync ${imageDir} | tail -1 | awk '{print $4}')
    filesCount=$(find ${imageDir}| wc -l)
    totalFilesSizeKb=$(du ${imageDir} | tail -1 | awk '{print $1}')

    log_message "partition $partition for $imageDir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

    #logMessage "totalFilesSizeKb = $totalFilesSizeKb"
    #logMessage "maximumAllowedSpaceTakenKb = $maximumAllowedSpaceTakenKb"
    #logMessage "spaceAvailableKb = $spaceAvailableKb"

    sleep 3

    cleanupPerformed=1
fi

log_message "removing old temporary h264 files."
/usr/sbin/tmpreaper -v --mtime 4h /etc/opt/kerberosio/h264/
check_errors $?

#publish topic
if [[ "$cleanupPerformed" = "1" ]]; then

    # prepare JSON message
    timestamp=$(date +%s)
    localTime=$(date '+%Y-%m-%d %H:%M:%S')
    totalDiskSpaceKb=$(df /  | tail -1 | awk '{print $2}')

    messageJson=$(cat <<EOF
    {
        "system_name":"${KD_SYSTEM_NAME}",
        "timestamp":"${timestamp}",
        "local_time":"${localTime}",
        "disk_space_available_kb":"${spaceAvailableKb}",
        "disk_space_total_kb":"${totalDiskSpaceKb}",
        "images_size_kb":"${totalFilesSizeKb}"
    }
EOF
    )

    messageJson=$(echo ${messageJson} | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')
    messageTopic="kerberos/files/removed"

    #publish it
    mosquitto_pub -h mqtt-server -t "$messageTopic" -m "$messageJson"
    check_errors $?


fi

log_message "cleanup finished."