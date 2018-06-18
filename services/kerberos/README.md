**Service: KerberosIO in container**

This core service is based on the Kerberos-IO (https://github.com/kerberos-io/) motion detection.  

This service is the heart of motion detection.

**Overwiev**

* Uses Raspberry Pi camera to record video
* Saves image/video files to shared volume on motion detection
* Publishes MQTT topic on motion detection

**Configuration files**

* /configs/kerberos

**Published MQTT topics**

* topic: **kerberos/machinery/detection/motion** with data (see: https://doc.kerberos.io/2.0/machinery/Outputs/MQTT)  

**Shared files**

* /data/kerberos/capture - captured media
* /data/kerberos/h264 - temporary media files
