#!/bin/bash

#modify local config
sed -i '/^log_dest/s/^/#/g' /etc/mosquitto/mosquitto.conf

#prepare bridge configuration if bridge is enabled
if [ "$KD_MQTT_BRIDGE_ENABLED" == "1" ]
then

    configFile=/etc/mosquitto/conf.d/bridge.conf
    echo "Creating the bridge config $configFile"
    echo "" > ${configFile}
    echo "connection bridge-to-therabithia" >> ${configFile}
    echo "address $KD_MQTT_BRIDGE_REMOTE_HOST:$KD_MQTT_BRIDGE_REMOTE_PORT" >> ${configFile}
    echo "remote_clientid $KD_SYSTEM_NAME" >> ${configFile}
    echo "remote_username $KD_MQTT_BRIDGE_REMOTE_USER" >> ${configFile}
    echo "remote_password $KD_MQTT_BRIDGE_REMOTE_PASSWORD" >> ${configFile}
    echo "topic # out 1 \"\" $KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX/" >> ${configFile}
    echo "topic # in 1" remote/ \"\">> ${configFile}

fi

while sleep 3; do
    echo "Starting the MQTT mosquitto server..."
    mosquitto -v -c /etc/mosquitto/mosquitto.conf
done

#sleep infinity
