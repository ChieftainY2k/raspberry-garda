**Service: email sender**

Simple PHP implementation to send emails with images on motion detection along with the health report.

This service handles email queue so that other services can easily request to send emails simply by creating JSON files in the email queue directory. 


**Email example**

![Email Example](./docs/images/email-motion-alert-example.png "Email example")

**Overview**
* Helper scripts:
  * **Topics collector** - subscribes to MQTT topics to collect payloads (motion detections and health report) and save them in files
  * **Topic queue processor** - scans topic queue directory, aggregates multiple motion detection payloads, creates email and puts email data into email queue    
  * **Email queue processor** - scans email queue directory for email data, sends emails via SMTP server

**Subscribed to MQTT topics**

* **kerberos/motiondetected**  
* **healthcheck/report**  

**Published MQTT topics**

* **notification/email/sent** - published when alert email is sent 

**Own shared resources**

* /data/email-notification/topic-queue - queue of the topics collected to be processesed by the topic processor   
* /data/email-notification/email-queues/default - queues of emails to be sent.    

**Used shared resources**

* /data/kerberos/capture - media from the kerberos service

**Configuration files**

* /configs/services.conf

**Sending an email with the email queue processor**

Any service/process can send an email with the email queue processor. 
Simply create new *.json file in the email queue directory with data in the following JSON format:

`````
    {
        "recipients":[
            "RECIPIENT EMAIL 1",
            "RECIPIENT EMAIL 2"
        ],
        "subject":"MY SUBJECT",
        "htmlBody":"MY HTML BODY",
        "attachments":[
            {
                "filePath":"FILE PATH 1"
            },
            {
                "filePath":"FILE PATH 2"
            }
        ]
    }
`````

The email queue processor will pick up the file, process it, create email with the data provided and send it using the SMTP server.

The file is removed after the email is successfully sent via the SMTP server.  