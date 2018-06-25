#!/bin/bash

#TODO optimize this script

#helper function
logMessage()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][autoremove]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

#LOGPREFIX="$(date '+%Y-%m-%d %H:%M:%S')[autoremove]"
imagedir=/etc/opt/kerberosio/capture/
partition=$(df $imagedir | awk '/^\/dev/ {print $1}')


usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
spaceAvailableKb=$(df --sync $imagedir | tail -1 | awk '{print $4}') # currently available free space on device
filesCount=$(find $imagedir| wc -l) # number of captured files
totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}') # total size of captured files
logMessage "partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

#max allowed space for files:
#maximumAllowedSpaceTakenKb=600000 # fixed = how much we allow files to take
maximumAllowedSpaceTakenKb=$(($spaceAvailableKb-500000)) # dynamic = related to the overall free space on device

cleanupPerformed=0
while [ $totalFilesSizeKb -gt $maximumAllowedSpaceTakenKb ]
do
    logMessage "cleaning up, removing some oldest files in $imagedir ..."
    find $imagedir -type f | sort | head -n 100 | xargs -r rm -rf;

    usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
    spaceAvailableKb=$(df --sync $imagedir | tail -1 | awk '{print $4}')
    filesCount=$(find $imagedir| wc -l)
    totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}')

    logMessage "partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"
    sleep 3

    cleanupPerformed=1
done

logMessage "removing old temporary h264 files."
tmpreaper -v 24h /etc/opt/kerberosio/h264/

#publish topic
if [[ "$cleanupPerformed" = "1" ]]; then

    messageJson=''
    messageTopic="kerberos/files/removed"

    #publish it
    mosquitto_pub -h mqtt-server -t "$messageTopic" -m "$messageJson"
    EXITCODE=$?
    if [ $EXITCODE -ne 0 ]; then
        logMessage "ERROR: there was an error publishing the MQTT topic."
    else
        logMessage "success, published MQTT topic $messageTopic with message $messageJson"
    fi


fi
