#!/bin/bash

DATE=`date '+%Y-%m-%d %H:%M:%S'`
imagedir=/etc/opt/kerberosio/capture/
#partition=/dev/root
partition=$(df $imagedir | awk '/^\/dev/ {print $1}')

#echo "Checking the free disk space:"
#echo "Image dir = $imagedir"
#echo "Partition = $partition"

usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
filesCount=$(find $imagedir| wc -l)
filesSize=$(du -h $imagedir | tail -1 | awk '{print $1}')
echo "[$DATE] Partition $partition for $imagedir is used in $usedPercent percent, has $filesCount files ($filesSize total)"

if [[ $usedPercent -gt 90 ]];
then
    echo "[$DATE] The disk space is LOW. Removing some of the oldest files in $imagedir ..."
    find $imagedir -type f | sort | head -n 100 | xargs -r rm -rf;

    usedPercent=$(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"])
    filesCount=$(find $imagedir| wc -l)
    filesSize=$(du -h $imagedir | tail -1 | awk '{print $1}')
    echo "[$DATE] Partition $partition for $imagedir is used in $usedPercent percent, has $filesCount files ($filesSize total)"

else
    echo "[$DATE] The disk space is OK"
fi;


echo "[$DATE] Removing old h264 files..."
tmpreaper -v 24h /etc/opt/kerberosio/h264/
echo "[$DATE] Old h264 files removed."

