#!/bin/bash

#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

if [[ "$KD_NGROK_ENABLED" == "1" ]]; then

    echo "Starting the NGROK tunnel client to the local kerberos webserver..."
    if [[ "$KD_NGROK_AUTHTOKEN" != "" ]]; then
        /sbin/ngrok authtoken ${KD_NGROK_AUTHTOKEN}
    else
        echo "Notice: Authtoken is empty, initializing tunnel as anonymous user."
    fi
    /sbin/ngrok http -log stdout kerberos:80 &
    echo "NGROK client initialized, wait..."
    sleep 10

    echo "NGROK status:"
    curl --silent --show-error http://127.0.0.1:4040/api/tunnels

    while sleep 1; do
        NGROK_TUNNEL_URL=$(curl --silent --show-error http://127.0.0.1:4040/api/tunnels | sed -nE 's/.*public_url":"https:..([^"]*).*/\1/p')
        echo "NGROK tunnel public url is $NGROK_TUNNEL_URL"
        sleep 600
    done

else

    echo "NGROK tunnel is DISABLED, exiting, sleeping for a while..."
    sleep 600

fi

