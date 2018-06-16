#!/bin/bash

DATE=`date '+%Y-%m-%d %H:%M:%S'`
imagedir=/etc/opt/kerberosio/capture/
partition=$(df $imagedir | awk '/^\/dev/ {print $1}')
minimumAcceptableSpaceKb=500000

#TODO optimize this

usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
kbytesAvailable=$(df $imagedir | tail -1 | awk '{print $4}')
filesCount=$(find $imagedir| wc -l)
filesSize=$(du -h $imagedir | tail -1 | awk '{print $1}')
echo "[$DATE] partition $partition for $imagedir is used in $usedPercent percent ($kbytesAvailable kb available), capture dir has $filesCount files (using $filesSize in total)"

#while [ $usedPercent -gt 90 ]
while [ $kbytesAvailable -lt $minimumAcceptableSpaceKb ]
do
    echo "[$DATE] running low on disk space, removing some of the oldest files in $imagedir ..."
    find $imagedir -type f | sort | head -n 100 | xargs -r rm -rf;

    usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
    bytesAvailable=$(df $imagedir | tail -1 | awk '{print $4}')
    filesCount=$(find $imagedir| wc -l)
    filesSize=$(du -h $imagedir | tail -1 | awk '{print $1}')
    echo "[$DATE] partition $partition for $imagedir is used in $usedPercent percent ($kbytesAvailable kb available), capture dir has $filesCount files (using $filesSize in total)"
done

echo "[$DATE] Removing old h264 files..."
tmpreaper -v 24h /etc/opt/kerberosio/h264/
echo "[$DATE] Old h264 files removed."

