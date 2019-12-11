#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][watchdog]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details."
        exit 1
    fi
}


log_message "checking internet connection..."

ping -c5 1.1.1.1
if [[ $? == 0 ]]
then

    echo "OK, network connection is working"

else

    log_message "NOTICE: no network connection!"

    log_message "restarting WLAN interfaces..."
    sudo ifconfig wlan0 down
    sleep 10
    sudo ifconfig wlan0 up

    log_message "restarting ETH interfaces..."
    sudo ifconfig eth0 down
    sleep 10
    sudo ifconfig eth0 up

    log_message "NOTICE: waiting for interfaces..."
    sleep 120

    #check if it worked
    ping -c5 1.1.1.1
    if [[ $? != 0 ]]
    then
        log_message "CRITICAL: still no network connection, rebooting..."
        sbin/shutdown -r now "Rebooting on network loss."
    fi

fi

