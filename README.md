![Overall diagram](./docs/images/kerberos-flow.png "Dockerized KerberosIO flow")

**Overview**

This project is an attempt at creating universal platform for service-based sensor/motion detection and notification system on Raspberry Pi 2/3.

The goal was to create platform for separated services that can be easily managed/updated/modified/developed/enabled/disabled.

This project was successfully tested with Raspberry Pi 3B+.

**How the platform works ?**

* Each service runs in a separate docker container
* The core services are
  * Kerberos-io motion detection service (https://github.com/kerberos-io/)
  * MQTT server/broker service (https://mosquitto.org/)
  * Services configurator (web based user interface to change services configuration and restart services)
* Docker containers are managed by docker-compose
* Containers have access to some shared file space (so that they can access media files etc.) 
* Services communicate with each other using MQTT topics with JSON payloads.
* A service may interact with some input/output hardware device (like camera, audio output, temperature sensors etc.) 
* A service may publish MQTT topics or subscribe to a topic to react accordingly. 
* A service may use remote services (like external MQTT server, SMTP server, IFTTT server etc.) to get its job done (like sending emails via a SMTP server).
* Want to know more what a service does ? Check out the /services directory and relevant README files

Enjoy! :-)
 

**Installation**

* Grab the newest Raspbian (Stretch Lite) from https://downloads.raspberrypi.org/raspbian_lite/images/raspbian_lite-2019-04-09/ , install it on a SD card (8GB at least).
* Run "**sudo apt-get -y update && sudo apt-get -y upgrade && sudo apt-get install -y git**" 
* Configure your time zone (raspi-config -> localisation -> change timezone)
* Enable the camera module support (raspi-config -> interfacing -> camera)
* Set video memory to 128MB (raspi-config -> advanced -> memory split)
* (optional) Disable the swap space (sudo systemctl disable dphys-swapfile && sudo reboot)
* Clone this repo (git clone REPO_URL), go to the newly created repo directory
* Edit the file **configs/environment.conf** and update it with your configuration (raspberry pi hardware version)
* Rename the file **configs/services.conf.template** to **configs/services.conf** then edit it and update with your configuration (like SMTP host/password etc.)
* Run "**sudo ./garda.sh install**" 
* Go to the kerberos installation page at http://_YOUR_RASPBERRY_PI_ADDRESS_

* The video stream is at http://_YOUR_RASPBERRY_PI_ADDRESS_:8889   
* The docker containers statistics are at http://_YOUR_RASPBERRY_PI_ADDRESS_:82   

The application will be automatically restarted on reboot, unless you explicitely stop it (see instructions below).

**Hardening (optional)**

* Change default passwords
* Use unattended upgrades
* Use Fail2Ban 

**Configuration (shell)**

* Edit the **configs/services.conf** file (if it doesn't exist then create it and copy the content from **configs/services.conf.template** file)
* Restart the services:
  * `docker-compose restart`

**Configuration (web GUI)**

* The services configurator is available at http://_YOUR_RASPBERRY_PI_ADDRESS_:85   


**Stop the system**
`````
sudo docker-compose stop 
`````

**Start up the system again**
`````
sudo docker-compose up -d 
`````

The containers will automatically restart on reboot/failure unless explicitly stopped 


**Show containers output/logs (last 10 lines, then follow the output)**
`````
sudo docker-compose logs -f --tail=10
`````

**Show kerberos Web Nginx logs**
`````
sudo docker-compose exec kerberos bash -c "tail -f /var/log/nginx/*"
`````

**Show Laravel logs**
`````
sudo docker-compose exec kerberos bash -c "tail -f /var/www/web/storage/logs/laravel.log"
`````

**Show webhook event listener logs**
`````
sudo docker-compose logs -f | grep webhook
`````

**Run bash inside kerberos container**
`````
sudo docker-compose exec kerberos bash
`````

