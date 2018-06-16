#!/bin/bash

#TODO optimize this script

DATE=`date '+%Y-%m-%d %H:%M:%S'`
imagedir=/etc/opt/kerberosio/capture/
partition=$(df $imagedir | awk '/^\/dev/ {print $1}')


usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
spaceAvailableKb=$(df --sync $imagedir | tail -1 | awk '{print $4}')
filesCount=$(find $imagedir| wc -l)
totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}')
echo "[$DATE] partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

#thresholds for cleanup policies
#maximumAllowedSpaceTakenKb=600000 # how much space we allow files to take
maximumAllowedSpaceTakenKb=$(($spaceAvailableKb-500000))

cleanupPerformed=0
while [ $totalFilesSizeKb -gt $maximumAllowedSpaceTakenKb ]
do
    echo "[$DATE] cleaning up, removing some oldest files in $imagedir ..."
    find $imagedir -type f | sort | head -n 100 | xargs -r rm -rf;

    usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
    bytesAvailable=$(df --sync $imagedir | tail -1 | awk '{print $4}')
    filesCount=$(find $imagedir| wc -l)
    totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}')
    echo "[$DATE] partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"
    sleep 3

    cleanupPerformed=1
done

echo "[$DATE] removing old temporary h264 files."
tmpreaper -v 24h /etc/opt/kerberosio/h264/

#publish topic
if [[ "$cleanupPerformed" = "1" ]]; then

    messageJson=''
    messageTopic="kios/files/removed"

    #publish it
    mosquitto_pub -h mqtt-server -t "$messageTopic" -m "$messageJson"
    EXITCODE=$?
    if [ $EXITCODE -ne 0 ]; then
        echo "[$DATE] ERROR: there was an error publishing the MQTT topic."
    else
        echo "[$DATE] success, published MQTT topic $messageTopic with message $messageJson"
    fi


fi

