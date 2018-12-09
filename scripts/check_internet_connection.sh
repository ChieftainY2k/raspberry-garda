#!/bin/bash
#

echo ""
echo "*** Check at `date`"

ping -c5 1.1.1.1
if [ $? == 0 ]
then

    echo "*** OK: Network connection is working"

else

    echo "*** ERROR: No network connection, restarting interfaces"
    service networking restart
    sleep 60

    #check if it worked
    ping -c5 1.1.1.1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Still no network connection, rebooting"
        sbin/shutdown -r now "Rebooting on network loss."
    fi

fi

