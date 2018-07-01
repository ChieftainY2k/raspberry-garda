**Service: configurator**

* This service exposes web GUI to modify the services configuration (see the file **configs/services.conf**).
* The service GUI is available at http://_YOUR_RASPBERRY_PI_ADDRESS_:85
* This service may reload containers when the configuration is updated.

**Published MQTT topics**

* **configurator/config/updated** - when services configuration gets updated 

