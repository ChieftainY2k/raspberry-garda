**Intro**
Kerberos-io in a dockerized Raspberry Pi 3 environment with local camera stream

**Run container from image**
`````
docker-compose up kerberos-deb
`````

**Rebuild image**
`````
docker-compose build kerberos-deb
`````

**Remove image**
`````
docker-compose rm -f  kerberos-deb
`````

**Machinery logs**
`````
docker-compose exec kerberos-deb tail -f /etc/opt/kerberosio/logs/log.stash
`````

**Container logs**
`````
docker-compose logs -f kerberos-deb
`````

**Nginx logs**
`````
docker-compose exec kerberos-deb bash -c "tail -f /var/log/nginx/*"
`````


**Run bash inside machinery container**
`````
docker-compose exec kerberos-deb bash
`````

**Event listener logs**
`````
docker-compose exec listener bash -c "tail -f /router.log"
`````

