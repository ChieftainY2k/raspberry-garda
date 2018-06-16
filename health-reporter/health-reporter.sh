#!/bin/bash


# Parts taken from https://gist.githubusercontent.com/ecampidoglio/5009512/raw/2efdb8535b30c2f8f9a391f055216c2a7f37e28b/cpustatus.sh

function convert_to_MHz {
    let value=$1/1000
    echo "$value"
}

function calculate_overvolts {
    # We can safely ignore the integer
    # part of the decimal argument
    # since it's not realistic to run the Pi
    # at voltages higher than 1.99 V
    let overvolts=${1#*.}-20
    echo "$overvolts"
}

temp=$(vcgencmd measure_temp)
temp=${temp:5:4}

volts=$(vcgencmd measure_volts)
volts=${volts:5:4}

if [ $volts != "1.20" ]; then
    overvolts=$(calculate_overvolts $volts)
fi

minFreq=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq)
minFreq=$(convert_to_MHz $minFreq)

maxFreq=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq)
maxFreq=$(convert_to_MHz $maxFreq)

freq=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq)
freq=$(convert_to_MHz $freq)

governor=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor)

DATE=`date '+%Y-%m-%d %H:%M:%S'`

echo -n "[$DATE] Hardware health report: "
echo -n "Temperature: $temp C"
echo -n ", voltage: $volts V"
[ $overvolts ] && echo -n " (+0.$overvolts overvolt)"
echo -n ", min speed: $minFreq MHz"
echo -n ", max speed: $maxFreq MHz"
echo -n ", current speed: $freq MHz"
echo -n ", governor: $governor"

availableDiskSpace=$(df | grep /dev/root | awk '{print $4/1}')

echo ", available disk space: $availableDiskSpace kb"

uptimeSeconds=$(echo $(awk '{print $1}' /proc/uptime) *100 /100 | bc)

# prepare JSON message
messageJson=$(cat <<EOF
{
  "cpu_temp": "$temp",
  "cpu_voltage": "$volts",
  "uptime_seconds": "$uptimeSeconds",
  "disk_space_available": "$availableDiskSpace"
}
EOF
)

messageJson=$(echo $messageJson | sed -z 's/\n/ /g' | sed -z 's/\"/\\\"/g')

#publish it
mosquitto_pub -h mqtt-server -t "healthreporter/report" -m "$messageJson"
EXITCODE=$?
if [ $EXITCODE -ne 0 ]; then
    echo "[$DATE] ERROR: there was an error publishing the topic."
else
    echo "[$DATE] Success, published MQTT topic."
fi

