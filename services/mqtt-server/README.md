**Service: MQTT queue server/broker**

This core service provides the MQTT server to all services

**Overview**

* Avaialble for all services at **mqtt-server:1883**
* All services must connect to this server to publish/subscribe to topics
* The server may act as a bridge and send/receive topics to/from external mqtt server and forward them to local/remote topics (useful for remote stats and checks).  

**Configuration**

* See the **/.env** file for details and topic bridge params (see http://www.steves-internet-guide.com/mosquitto-bridge-configuration/)

**Example of working bridge publishing topics to remote server**

![Screenshot](./docs/images/mqtt-bridge-remote-topics.png "Screenshot")

