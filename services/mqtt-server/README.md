**Service: MQTT queue server/broker**

This core service provides the local MQTT server to all local services

**Overview**

* Avaialble for all local services at **mqtt-server:1883**
* All services must connect to this server to publish/subscribe to topics
* The server may act as a bridge and send/receive topics to/from external mqtt server and forward them to local/remote topics (useful for remote stats and checks).  

**Configuration files**

* See `/configs/services.conf` 

**Service configuration**

Note: for details and topic bridge params (see http://www.steves-internet-guide.com/mosquitto-bridge-configuration/)

* `KD_MQTT_BRIDGE_ENABLED` , set `0` to disable, `1` to enable
* `KD_MQTT_BRIDGE_REMOTE_HOST`  
* `KD_MQTT_BRIDGE_REMOTE_PORT`
* `KD_MQTT_BRIDGE_REMOTE_USER`
* `KD_MQTT_BRIDGE_REMOTE_PASSWORD`
* `KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX` - prefix (namespace) to be assigned all outgoing topics going to the remote MQTT server 

**Service configuration example**

`````
KD_MQTT_BRIDGE_ENABLED=1
KD_MQTT_BRIDGE_REMOTE_HOST=m21.cloudmqtt.com
KD_MQTT_BRIDGE_REMOTE_PORT=12345
KD_MQTT_BRIDGE_REMOTE_USER=abcdefg
KD_MQTT_BRIDGE_REMOTE_PASSWORD=sdvwergDFBwsf
KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX=MyRaspberryGarda
`````

**MQTT Bridge**

* Incoming remote->local topics have the `remote/` prefix attached
* Outgoing local->remote topics have the `KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX/` prefix attached

**Example of working bridge publishing topics to remote server**

![Screenshot](./docs/images/mqtt-bridge-remote-topics.png "Screenshot")

