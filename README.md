**Intro**

Kerberos-io (https://github.com/kerberos-io/) in a dockerized Raspberry Pi 3 environment with local camera stream (with RaspiCam)


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

**Start up the system again (in daemon mode)**

Note: The containers will automatically restart on reboot/failure unless explicitly stopped 

`````
docker-compose up -d 
`````

**Show containers output**
`````
docker-compose logs -f 
`````

**Show containers statistics/usage (cadvisor)**
`````
http://RASPBERRY_IP:8090/ 
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

**Show webhook event listener logs**
`````
docker-compose exec webhook-php bash -c "tail -f /listener.log"
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

