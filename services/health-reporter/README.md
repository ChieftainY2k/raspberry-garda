**Service: Health reporter**

The health reporter service gathers some hardware and system statistics (like CPU load, temperature, disk space etc.) and publishes it as the MQTT topic so that all subscribers/services can react accordingly.

**Published MQTT topics**

* topic: **healthcheck/report** with statistics data 


