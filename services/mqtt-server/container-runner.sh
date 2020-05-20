#!/bin/bash

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][$(basename $0)]"
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

#check for errors
check_errors_warning()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , there were some errors - check the ouput for details."
    fi
}


#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')
check_errors $?

# fix permissions
log_message "fixing permissions..."
chmod u+x /code/container-healthcheck.sh
check_errors $?

#modify local config - disable logging to file
sed -i '/^log_dest/s/^/#/g' /etc/mosquitto/mosquitto.conf
check_errors $?

#disable bridge by default
configFile="/etc/mosquitto/conf.d/bridge.conf"
echo "" > ${configFile}
check_errors $?

#prepare bridge configuration if bridge is enabled
if [[ "$KD_MQTT_BRIDGE_ENABLED" == "1" ]]; then

    log_message "Creating the bridge config $configFile"
    echo "connection bridge-to-therabithia" >> ${configFile}
    echo "address $KD_MQTT_BRIDGE_REMOTE_HOST:$KD_MQTT_BRIDGE_REMOTE_PORT" >> ${configFile}
    echo "remote_clientid $KD_SYSTEM_NAME" >> ${configFile}
    echo "remote_username $KD_MQTT_BRIDGE_REMOTE_USER" >> ${configFile}
    echo "remote_password $KD_MQTT_BRIDGE_REMOTE_PASSWORD" >> ${configFile}
    echo "topic # out 1 \"\" $KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX/" >> ${configFile}
    echo "topic # in 1 remote/ \"\"">> ${configFile}
    check_errors $?

else

    log_message "WARNING: MQTT bridge is DISABLED, local MQTT messages will not leave local server."

fi

# Fix permissions
chmod a+rwx /var/lib/mosquitto
check_errors $?

while sleep 1; do
    log_message "Starting the MQTT mosquitto server..."
    mosquitto -v -c /etc/mosquitto/mosquitto.conf
    check_errors_warning $?
    log_message "MQTT mosquitto server stopped, sleeping and starting again..."
    sleep 60
done

#sleep infinity
