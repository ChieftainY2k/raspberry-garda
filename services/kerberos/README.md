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

* topic: **kerberos/motiondetected** with data (see: http://doc.kerberos.io/opensource/machinery#mqtt)  
* topic: **kerberos/files/removed** - when some media files are removed to free the disk space  

**Shared files**

* /data/kerberos/capture - captured media
* /data/kerberos/h264 - temporary media files
