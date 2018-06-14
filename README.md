**Intro**

This project is an attempt at running Kerberos-io (https://github.com/kerberos-io/) instance in a docker container on Raspberry Pi 2/3 hardware and use the native Raspberry Camera Module.

The kerberos container is managed by the docker-compose along with other helper containers (like simple webhooks listener in a separate container).

Enjoy! :-)
 

**Installation**

* Grab the newest Raspbian (Stretch Lite) from https://www.raspberrypi.org/downloads/ , install it on a SD card.
* Fire up your RPI device, enable the camera support (raspi-config)
* Clone this repo (git clone)
* Run "**bash ./install.sh**"

* Kerberos installation page is at http://_YOUR_RASPBERRY_PI_ADDRESS_    
* The video stream is at http://_YOUR_RASPBERRY_PI_ADDRESS_:8889   
* The docker statistics are at http://_YOUR_RASPBERRY_PI_ADDRESS_:8090   

The application will be automatically restarted on reboot, unless you explicitely stop it (see instructions below).


**Stop the system**
`````
docker-compose stop 
`````

**Start up the system again**

`````
docker-compose up -d 
`````

The containers will automatically restart on reboot/failure unless explicitly stopped 


**Show containers output/logs**
`````
docker-compose logs -f 
`````

**Show kerberos machinery logs**
`````
tail -f data/logs/log.stash
`````

**Show kerberos machinery cron autoremove logs**
`````
tail -f data/logs/autoremoval.log
`````

**Show kerberos Web Nginx logs**
`````
docker-compose exec kerberos-deb bash -c "tail -f /var/log/nginx/*"
`````

**Show webhook event listener logs**
`````
docker-compose logs -f | grep webhook
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

**Run bash inside machinery container**
`````
docker-compose exec kerberos-deb bash
`````

