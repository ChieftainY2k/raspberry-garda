**Service: NGROK tunnel client**

This service uses ngrok (see https://ngrok.com/) tunnel allowing you to access your local kerberos administration panel with public URL from anywhere in the world.  

**Configuration files**

* See `/configs/services.conf` 

**Service configuration**

* `KD_NGROK_ENABLED=0` to disable service 
* `KD_NGROK_ENABLED=1` to enable service
* `KD_NGROK_AUTHTOKEN=TOKEN` to provide ngrok auth key

**Service configuration example**

`````
KD_NGROK_ENABLED=1
KD_NGROK_AUTHTOKEN=3ZZsdvws23vsdvsfdfq4w_3wsacwewewewvvwvoxUfx
`````

