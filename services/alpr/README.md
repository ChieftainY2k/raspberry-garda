**Service: automatic license plate recognition**

This service uses openALPR (automatic license plate recognition) to analyze images and find car registration numbers.

![](./docs/images/license-plate-example.png "")
![](./docs/images/alpr-result-topic.png "")
 
**Overview**

* Subscribes to the MQTT topic **kerberos/motiondetected**
* Topic payloads are picked up by topic collector and saved in local queue
* Saved topics are picked up from the local queue by the queue processor 
* Queue processor uses openALPR to analyze an image and find possible plate numbers
* Upon successfull detection service publishes MQTT topic with the list of guessed numbers
* Guessed numbers are saved in the shared directory


**Subscribed to MQTT topics**

* **kerberos/motiondetected** - this topic triggers alpr to find license plates in an image  

**Published MQTT topics**

* **alpr/detection** - with (best guess) car registration plate numbers in the JSON payload  

**Own shared resources**

* /data/alpr/recognized-numbers - data with recognized numbers  
* /data/alpr/topics-queue - queue of the topics collected to be processesed by the alpr   

**Used shared resources**

* /data/kerberos/capture - media from the kerberos service

**Configuration files**

* /configs/services.conf

**Known issues**

* Alpr service is very CPU intensive.
   

