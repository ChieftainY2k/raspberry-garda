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
 

**Raspberry preparation**

* Grab the Raspbian Buster Lite from https://downloads.raspberrypi.org/ , install it on a SD card 
  (8GB at least, 32GB would be nice, [pick the fastest SD card you can afford](https://www.pidramble.com/wiki/benchmarks/microsd-cards)).  
* Update packages: `sudo apt-get -y update && sudo apt-get -y upgrade` 
* Configure your time zone (`raspi-config -> localisation -> change timezone`)
* Configure your host name (`raspi-config -> networking -> hostname`)
* Enable the camera module support (`raspi-config -> interfacing -> camera`)
* If RAM is less than 500MB set video memory to 8MB (`raspi-config -> advanced -> memory split`)
* If camera will be used set video memory to 128MB (`raspi-config -> advanced -> memory split`)
* If RAM is less than 500MB increase the swap space (edit the `/etc/dphys-swapfile` , set `CONF_SWAPSIZE=500`)
* Reboot

**(OPTIONAL): Enable support for 1-Wire**
If you plan to use thermometer service you must enable the 1-wire interface
`````
echo "dtoverlay=w1-gpio" >> /boot/config.txt
echo "w1-gpio" >> /etc/modules
echo "w1-therm" >> /etc/modules
reboot
`````

**(OPTIONAL) Disable camera LED light**
To disable the red camera LED run the following commands:  
`````
echo "disable_camera_led=1" >> /boot/config.txt
reboot
`````

**(OPTIONAL) Raspberry peformance tuning**
* Set CPU overclocking to max available value (`raspi-config -> overclock`)
* Check filesystem on every boot (put `fsck.mode=force` at the end of line in `/boot/cmdline.txt`) 
* Harden against brute-force ssh password guessing attacks (`apt-get -y install fail2ban`)
* Disable bluetooth (see https://scribles.net/disabling-bluetooth-on-raspberry-pi) 
* Reboot

**(OPTIONAL) Configure startup scripts to send an email on each reboot**
* Install email tools: `apt-get install -y mailutils msmtp msmtp-mta` 
* Edit ssmtp config `/etc/msmtprc` with your config (gmail.com as an example below):
`````
defaults
auth           on
tls            on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
account gmail.com
host smtp.gmail.com
port 587
from xxxxxxxxxxxxxx@gmail.com
user xxxxxxxxxxxxxx
password yyyyyyyyyyyyyyyyyyyy
account default : gmail.com

````` 
* Edit local startup script `nano /etc/rc.local` , add the following snippet:
`````
_IP=$(hostname -I) || true
if [ "$_IP" ]; then
  printf "My IP address is %s\n" "$_IP"
fi

echo "sending emails..."
SUBJECT="$(hostname) restarted"
BODY=" $(hostname) restarted. Local IP is $_IP "
echo "$BODY" | mail -s "$SUBJECT" YOUR_GMAIL_USER@gmail.com 
````` 

**Garda Installation**

1. Clone this repository to a directory of your choice (preferably `$HOME/raspberry-garda/`):  `apt-get -y install git && git clone https://github.com/ChieftainY2k/raspberry-garda.git`
1. Rename the file `configs/services.conf.template` to `configs/services.conf` then edit it and update with your configuration (like SMTP host/password etc.)
1. Run `sudo ./garda.sh install` to install everything needed
1. Run `sudo ./garda.sh check` to check environment and hardware

**(OPTIONAL) Garda watchdog installation**

Run `sudo ./garda.sh watchdog install` to install garda watchdog scripts to reboot host or perform some other "last resort" operations when something is wrong (i.e. internet connection is lost)

**(OPTIONAL) Hardware watchdog installation**

Configure low-level system watchdog: `sudo ./garda.sh watchdog installhardware`

**Starting up Raspberry Garda**

1. Run `sudo ./garda.sh start`
1. Go to the kerberos installation page at `http://_YOUR_RASPBERRY_PI_ADDRESS_/kerberos`
1. Check the video stream at `http://_YOUR_RASPBERRY_PI_ADDRESS_/kerberos/stream`

Note: The application services will be automatically restarted on reboot, unless you explicitely stop it (see instructions below).

**Configuration (shell)**

1. Edit the `configs/services.conf` file (if it doesn't exist then create it and copy the content from `configs/services.conf.template` file)
1. Restart the services:
  `./garda.sh restart`

**Configuration (web GUI)**

1. Edit `configs/services.conf` and set the `KD_UI_USER` `KD_UI_PASSWORD` values with a password of your choice
1. Go to `http://_YOUR_RASPBERRY_PI_ADDRESS_/`   

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

**Show/follow service logs**
`````
./garda.sh log [SERVICE]
`````
Example:
`````
./garda.sh log kerberos
`````
Example:
`````
docker logs garda_historian_1 --since=2020-03-30T23:30 2>&1 | grep "GarbageCollector"
`````


**Run bash inside SERVICE container**
`````
./garda.sh shell [SERVICE]
`````
Example:
`````
./garda.sh shell kerberos
`````

**Hardening (optional)**

* Change default passwords
* Use unattended upgrades
* Use Fail2Ban 
* Put your IoT device behind second NAT/router 


**Troubleshooting**

  * Raspberry loses ipv4 connection after docker container starts. Solution: add `ipv6.disable=1` do `/boot/cmdline.txt` then reboot. 
    



