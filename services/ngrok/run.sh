#!/bin/bash

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

if [[ "$KD_NGROK_ENABLED" == "1" ]]; then

    while sleep 3; do
        echo "Starting the NGROK tunnel client to kerberos server..."
        /sbin/ngrok authtoken ${KD_NGROK_AUTHTOKEN}
        /sbin/ngrok http kerberos:80
        echo "NGROK tunnel client stopped, sleeping for a while..."
        sleep 600
    done

else

    echo "NGROK tunnel is DISABLED."
    sleep infinity

fi



