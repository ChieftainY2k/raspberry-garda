#!/bin/bash

partition=/dev/root
imagedir=/etc/opt/kerberosio/capture/
if [[ $(df -h | grep $partition | head -1 | awk -F' ' '{ print $5/1 }' | tr ['%'] ["0"]) -gt 90 ]];
then
    echo "Cleaning disk"
    find $imagedir -type f | sort | head -n 100 | xargs -r rm -rf;
fi;
