**MQTT queue listener and email sender**

Simple PHP implementataion to send email with images upon motion detection.

The flow:
* Kerberos detects motion and sends MQTT topic
* Topic is picked up by php subscriber and saved in local queue
* Queue processor takes data from local queue and creates email with topic data and captured files.





