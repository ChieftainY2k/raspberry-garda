**Service: event historian**

This service saves all local and remove MQTT events in local sqlite database 

**Service configuration**

* `KD_HISTORIAN_ENABLED=0` to disable service 
* `KD_HISTORIAN_ENABLED=1` to enable service

**Subscribed to MQTT topics**

* **all**  

**Published MQTT topics**

**Own shared resources**

* /data/historian/mqtt-history.sqlite   

**Configuration files**

* /configs/services.conf

