**Service: Thermometer**

**Overview**

* Monitors **DS18B20** temperature sensors

**Service configuration**

* `KD_THERMOMETER_ENABLED=0` to disable service 
* `KD_THERMOMETER_ENABLED=1` to enable service
* `KD_THERMOMETER_ALIASES` - JSON key-value table for sensor human readable alisaes

**Service configuration example**

`````
KD_THERMOMETER_ENABLED=1
KD_THERMOMETER_ALIASES={"28-0516a03222ff":"sensor_1","28-0516a038b5ff":"sensor_2"}
````` 

**Hardware preparation**

* Step 1: Enable support for 1-Wire

`````
echo "dtoverlay=w1-gpio" >> /boot/config.txt
echo "w1-gpio" >> /etc/modules
echo "w1-therm" >> /etc/modules
reboot
`````

* Step 2: get to know the pinout of your raspberry
`````
./garda.sh exec thermometer pinout
`````

* Step 3: connect your DS18B20 temperature sensor to corresponding pins.

Check it out: https://www.google.com/search?q=DS18B20+raspberry+pi+installation

* Step 4: check if we can read from the temperature sensors
`````
./garda.sh check
`````

**Configuration files**

...TBD...

**Published MQTT topics**

...TBD...

**Shared files**

...TBD...