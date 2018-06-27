#!/bin/bash

#helper function
logMessage()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][rebuild]"
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

logMessage "Stopping containers..."
docker-compose stop
check_errors $?

logMessage "Removing containers..."
docker-compose rm -f
check_errors $?

logMessage "Starting containers..."
docker-compose up -d --remove-orphans --build
check_errors $?

