#!/bin/bash

#TODO optimize this script

#helper function
logMessage()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][autoremove]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

logMessage "cleanup started."

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

#LOGPREFIX="$(date '+%Y-%m-%d %H:%M:%S')[autoremove]"
imagedir=/etc/opt/kerberosio/capture/
partition=$(df $imagedir | awk '/^\/dev/ {print $1}')


usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
#spaceTotalKb=$(df --sync $imagedir | tail -1 | awk '{print $1}') # total space (free+used)
spaceUsedKb=$(df --sync $imagedir | tail -1 | awk '{print $2}') # used space
spaceAvailableKb=$(df --sync $imagedir | tail -1 | awk '{print $4}') # currently available free space on device
filesCount=$(find $imagedir| wc -l) # number of captured files
totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}') # total size of captured files
logMessage "partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

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
    logMessage "cleaning up, removing some oldest files in $imagedir ..."
    find $imagedir -type f | sort | head -n 100 | xargs -r rm -rf;

    usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
#    spaceTotalKb=$(df --sync $imagedir | tail -1 | awk '{print $1}') # total space (free+used)
    spaceUsedKb=$(df --sync $imagedir | tail -1 | awk '{print $2}') # used space
    spaceAvailableKb=$(df --sync $imagedir | tail -1 | awk '{print $4}')
    filesCount=$(find $imagedir| wc -l)
    totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}')

    logMessage "partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

    #logMessage "totalFilesSizeKb = $totalFilesSizeKb"
    #logMessage "maximumAllowedSpaceTakenKb = $maximumAllowedSpaceTakenKb"
    #logMessage "spaceAvailableKb = $spaceAvailableKb"

    sleep 3

    cleanupPerformed=1
fi

logMessage "removing old temporary h264 files."
tmpreaper -v --mtime 24h /etc/opt/kerberosio/h264/

#publish topic
if [[ "$cleanupPerformed" = "1" ]]; then

    # prepare JSON message
    timestamp=$(date +%s)
    localTime=$(date '+%Y-%m-%d %H:%M:%S')
    totalDiskSpaceKb=$(df /  | tail -1 | awk '{print $2}')

    messageJson=$(cat <<EOF
    {
        "system_name":"${KD_SYSTEM_NAME}",
        "timestamp":"$timestamp",
        "local_time":"$localTime",
        "disk_space_available_kb":"$spaceAvailableKb",
        "disk_space_total_kb":"$totalDiskSpaceKb",
        "images_size_kb":"$totalFilesSizeKb"
    }
EOF
    )

    messageJson=$(echo $messageJson | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')
    messageTopic="kerberos/files/removed"

    #publish it
    mosquitto_pub -h mqtt-server -t "$messageTopic" -m "$messageJson"
    EXITCODE=$?
    if [[ ${EXITCODE} -ne 0 ]]; then
        logMessage "ERROR: there was an error publishing the MQTT topic."
    else
        logMessage "success, published MQTT topic $messageTopic with message $messageJson"
    fi


fi

logMessage "cleanup finished."