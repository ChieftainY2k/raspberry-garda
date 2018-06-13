**Intro**

Kerberos-io in a dockerized Raspberry Pi 3 environment with local camera stream (with RaspiCam)


**Installation**

* Grab the newest Raspbian from https://www.raspberrypi.org/downloads/ , install it on a SD card.
* Fire up your RPI device, enable the camera support (raspi-config)
* Install docker  (https://raw.githubusercontent.com/ChieftainY2k/raspberrypi-docker-box/master/install-docker.sh | bash)
* Clone this repo (git clone)


**Start up the system**
`````
docker-compose -d up 
`````

**Stop the system**
`````
docker-compose stop 
`````

**Run only kerberos container from image**
`````
docker-compose up kerberos-deb
`````

**Rebuild kerberos image**
`````
docker-compose build kerberos-deb
`````

**Remove kerberos image**
`````
docker-compose rm -f  kerberos-deb
`````

**Show kerberos machinery logs**
`````
docker-compose exec kerberos-deb tail -f /etc/opt/kerberosio/logs/log.stash
`````

**Show kerberos container logs**
`````
docker-compose logs -f kerberos-deb
`````

**Show kerberos Web Nginx logs**
`````
docker-compose exec kerberos-deb bash -c "tail -f /var/log/nginx/*"
`````


**Run bash inside machinery container**
`````
docker-compose exec kerberos-deb bash
`````

**Event listener logs**
`````
docker-compose exec listener bash -c "tail -f /listener.log"
`````

