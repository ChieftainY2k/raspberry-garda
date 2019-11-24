#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][mqtt-server]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
        exit 1
    fi
}


#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

#modify local config - disable logging to file
sed -i '/^log_dest/s/^/#/g' /etc/mosquitto/mosquitto.conf

#disable bridge by default
configFile=/etc/mosquitto/conf.d/bridge.conf
echo "" > ${configFile}

#prepare bridge configuration if bridge is enabled
if [[ "$KD_MQTT_BRIDGE_ENABLED" == "1" ]]; then

    echo "Creating the bridge config $configFile"
    echo "connection bridge-to-therabithia" >> ${configFile}
    echo "address $KD_MQTT_BRIDGE_REMOTE_HOST:$KD_MQTT_BRIDGE_REMOTE_PORT" >> ${configFile}
    echo "remote_clientid $KD_SYSTEM_NAME" >> ${configFile}
    echo "remote_username $KD_MQTT_BRIDGE_REMOTE_USER" >> ${configFile}
    echo "remote_password $KD_MQTT_BRIDGE_REMOTE_PASSWORD" >> ${configFile}
    echo "topic # out 1 \"\" $KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX/" >> ${configFile}
    echo "topic # in 1" remote/ \"\">> ${configFile}

else

    echo "MQTT bridge is DISABLED."

fi

# Fix permissions
chmod a+rwx /var/lib/mosquitto
check_errors $?

while sleep 1; do
    echo "Starting the MQTT mosquitto server..."
    mosquitto -v -c /etc/mosquitto/mosquitto.conf
    check_errors $?
    sleep 60
done

#sleep infinity
