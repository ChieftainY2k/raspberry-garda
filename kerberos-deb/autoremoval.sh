#!/bin/bash

#thresholds for cleanup policies
maximumAllowedSpaceTakenKb=600000 # how much space we allow files to take

DATE=`date '+%Y-%m-%d %H:%M:%S'`
imagedir=/etc/opt/kerberosio/capture/
partition=$(df $imagedir | awk '/^\/dev/ {print $1}')

#TODO optimize this
usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
spaceAvailableKb=$(df --sync $imagedir | tail -1 | awk '{print $4}')
filesCount=$(find $imagedir| wc -l)
totalFilesSizeKb=$(du $imagedir | tail -1 | awk '{print $1}')
echo "[$DATE] partition $partition for $imagedir is used in $usedPercent percent ($spaceAvailableKb kb available), capture dir has $filesCount files (using $totalFilesSizeKb kb in total)"

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
done

echo "[$DATE] removing old temporary h264 files."
tmpreaper -v 24h /etc/opt/kerberosio/h264/


