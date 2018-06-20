**Service: alert email sender**

This service uses alpr (automatic license plate recognition) to analyze images and find car registration plates. 

**Subscribed to MQTT topics**

* **kerberos/machinery/detection/motion** - this topic triggers alpr to find license plates in an image  

**Published MQTT topics**

* **alpr/recognition/detection** with found car registration plates in the JSON payload  

**Shared files**

* /data/kerberos/capture - captured media  

