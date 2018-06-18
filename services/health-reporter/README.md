**Service: Health reporter**

The health reporter service gathers some hardware and system statistics (like CPU load, temperature, disk space etc.) and publishes it as the MQTT topic so that all subscribers/services can react accordingly.

**Published MQTT topics**

* topic: **healthcheck/report** with such data as:
  * cpu_temp
  * cpu_voltage
  * uptime_seconds
  * disk_space_available_kb
  * disk_space_total_kb 


