#!/bin/bash

#helper function
logMessage()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][restart]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    EXITCODE=$1
    if [ $EXITCODE -ne 0 ]; then
        logMessage "ERROR: there were some errors, check the ouput for details, press ENTER to continue or Ctrl-C to abort."
        read
    else
        logMessage "OK, operation successfully completed."
    fi
}

export COMPOSE_HTTP_TIMEOUT=200

logMessage "Restarting containers..."
docker-compose restart
check_errors $?
