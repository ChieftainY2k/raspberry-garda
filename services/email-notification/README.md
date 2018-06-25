**Service: alert email sender**

Simple PHP implementation to send email with images upon motion detection.

**Email example**

![Email Example](./docs/images/email-motion-alert-example.png "Email example")

**Overview**
* Subscribes to the MQTT topic **kerberos/motiondetected**
* Topic payloads are picked up by topic collector and saved in local queue
* Saved topics are picked up from the local queue by the queue processor which creates emails with alerts and sends via external SMTP server.

**Subscribed to MQTT topics**

* **kerberos/motiondetected**  

**Published MQTT topics**

* **notification/email/sent** - published when alert email is sent 

**Own shared resources**

* /data/email-notification/topics-queue - queue of the topics collected to be processesed by the email creator   

**Used shared resources**

* /data/kerberos/capture - media from the kerberos service

**Configuration files**

* /configs/services.conf

