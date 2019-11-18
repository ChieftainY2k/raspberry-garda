![Overall diagram](./docs/images/kerberos-flow.png "Dockerized KerberosIO flow")

**Overview**

This project is an attempt at creating universal platform for service-based sensor/motion detection and notification system on Raspberry Pi 2/3.

The goal was to create platform for separated services that can be easily managed/updated/modified/developed/enabled/disabled.

This project was successfully tested with **Raspberry Pi 2** and **Raspberry Pi 3**.

**How the platform works ?**

* Each service runs in a separate docker container
* The core services are
  * Kerberos-io motion detection service (https://github.com/kerberos-io/)
  * MQTT server/broker service (https://mosquitto.org/)
  * Configurator (web based user interface to change services configuration and restart services)
  * Email sender 
* Docker containers are managed by docker-compose
* Containers have access to some shared file space (so that they can access media files etc.) 
* A service may publish MQTT topics or subscribe to a topic to react accordingly. 
* Services communicate with each other using the local MQTT server (topics with JSON payloads).
* A service may interact with some input/output hardware device (like camera, audio output, temperature sensors etc.) 
* A service may use remote services (like external MQTT server, SMTP server, IFTTT server etc.) to get its job done (like sending emails via a SMTP server).
* Want to know more what a service does ? Check out the /services directory and relevant README files

Enjoy! :-)
 

**Installation**

1. Grab the newest Raspbian (Stretch Lite) from https://downloads.raspberrypi.org/raspbian_lite/images/raspbian_lite-2019-04-09/ , install it on a SD card (8GB at least, 16GB would be nice).
1. (optional) Update your raspberry: `sudo apt-get -y update && sudo apt-get -y upgrade` 
1. Configure your time zone (`raspi-config -> localisation -> change timezone`)
1. Enable the camera module support (`raspi-config -> interfacing -> camera`)
1. Set video memory to 128MB (`raspi-config -> advanced -> memory split`)
1. (optional) Disable the swap space (`sudo systemctl disable dphys-swapfile && sudo reboot`)
1. Clone this repository do a directory of your choice
1. Edit the file `configs/environment.conf` and update it with your configuration (raspberry pi hardware version)
1. Rename the file `configs/services.conf.template` to `configs/services.conf` then edit it and update with your configuration (like SMTP host/password etc.)
1. Run `sudo ./garda.sh install`


**Starting up**

1. Run `sudo ./garda.sh start`
1. Go to the kerberos installation page at `http://_YOUR_RASPBERRY_PI_ADDRESS_`
1. Check the video stream at `http://_YOUR_RASPBERRY_PI_ADDRESS_/stream`

Note: The application services will be automatically restarted on reboot, unless you explicitely stop it (see instructions below).

**Configuration (shell)**

1. Edit the `configs/services.conf` file (if it doesn't exist then create it and copy the content from `configs/services.conf.template` file)
1. Restart the services:
  `./garda.sh restart`

**Configuration (web GUI)**

1. Edit `configs/services.conf` and set the `KD_UI_USER` `KD_UI_PASSWORD` values with a password of your choice
1. Go to `http://_YOUR_RASPBERRY_PI_ADDRESS_/configurator`   

**Stop the system**
`````
./garda.sh stop 
`````

**Start up the system again**
`````
./garda.sh start 
`````

**Show containers output/logs (last 10 lines, then follow the output)**
`````
./garda.sh logs
`````

**Show kerberos logs**
`````
./garda.sh kerberos log
`````

**Run bash inside kerberos container**
`````
./garda.sh kerberos bash
`````

**Hardening (optional)**

* Change default passwords
* Use unattended upgrades
* Use Fail2Ban 
* Put your IoT device behind second NAT/router 

