**Service: KerberosIO in container**

This service is based on the Kerberos-IO (https://github.com/kerberos-io/) video surveillance and motion detection system.  

**Overwiev**

* Uses Raspberry Pi camera to record video
* Saves image/video files to shared volume on motion detection
* Publishes MQTT topic on motion detection

**Configuration files**

* See `/configs/services.conf` 

**Service configuration**

* `KD_KERBEROS_ENABLED=0` to disable service 
* `KD_KERBEROS_ENABLED=1` to enable service

**Web interface **

1. User interface: `http://_YOUR_RASPBERRY_PI_ADDRESS_/kerberos`
1. Video stream: `http://_YOUR_RASPBERRY_PI_ADDRESS_/kerberos/stream`


**Published MQTT topics**

* topic: `kerberos/motiondetected` with data (see: https://doc.kerberos.io/2.0/machinery/Outputs/MQTT)  
* topic: `kerberos/files/removed` - when some media files are removed to free the disk space  

**Shared files**

* `/data/kerberos/capture` - captured media
* `/data/kerberos/h264` - temporary media files
