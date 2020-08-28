#!/bin/bash

# Parts taken from https://gist.githubusercontent.com/ecampidoglio/5009512/raw/2efdb8535b30c2f8f9a391f055216c2a7f37e28b/cpustatus.sh

#helper function
log_message()
{
    LOGPREFIX="[$(date '+%Y-%m-%d %H:%M:%S')][$(basename $0)]"
    MESSAGE=$1
    echo "$LOGPREFIX $MESSAGE"
}

#check for errors
check_errors()
{
    local EXITCODE=$1
    if [[ ${EXITCODE} -ne 0 ]]; then
        log_message "ERROR: Exit code ${EXITCODE} , check the ouput for details."
        exit 1
    fi
}

function convert_to_MHz {
    let value=$1/1000
    echo "$value"
}

#function calculate_overvolts {
#    # We can safely ignore the integer
#    # part of the decimal argument
#    # since it's not realistic to run the Pi
#    # at voltages higher than 1.99 V
#    let overvolts=${1#*.}-20
#    echo "$overvolts"
#}


#load services configuration
export $(grep -v '^#' /service-configs/services.conf | xargs -d '\n')

temp=$(vcgencmd measure_temp)
temp=${temp:5:4}

volts=$(vcgencmd measure_volts)
volts=${volts:5:4}

#if [[ ${volts} != "1.20" ]]; then
#    overvolts=$(calculate_overvolts ${volts})
#fi

#TODO change to vcgencmd ?
minFreq=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq)
minFreqMhz=$(convert_to_MHz ${minFreq})

#TODO change to vcgencmd ?
maxFreq=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq)
maxFreqMhz=$(convert_to_MHz ${maxFreq})

#TODO change to vcgencmd ?
freq=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq)
freqMhz=$(convert_to_MHz ${freq})

governor=$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor)

DATE=`date '+%Y-%m-%d %H:%M:%S'`

#echo -n "[$DATE] current hardware stats: "
#echo -n "Temperature: $temp C"
#echo -n ", voltage: $volts V"
#[[ ${overvolts} ]] && echo -n " (+0.$overvolts overvolt)"
#echo -n ", min speed: $minFreq MHz"
#echo -n ", max speed: $maxFreq MHz"
#echo -n ", current speed: $freq MHz"
#echo -n ", governor: $governor"

# @FIXME use env/config var to get media directory here
availableDiskSpaceKb=$(df / | tail -1  | awk '{print $4/1}')
totalDiskSpaceKb=$(df /  | tail -1 | awk '{print $2}')

#echo -n ", total disk space: $totalDiskSpaceKb kb"
#echo ", available disk space: $availableDiskSpaceKb kb"

#imagedir=/etc/opt/kerberosio/capture/

uptimeInfo=$(uptime)
uptimeBootDate=$(uptime -s)
uptimeSeconds=$(echo $(awk '{print $1}' /proc/uptime) *100 /100 | bc)
timestamp=$(date +%s)
localTime=$(date '+%Y-%m-%d %H:%M:%S')
#totalFilesSizeKb=$(du ${imagedir} | tail -1 | awk '{print $1}') # total size of captured files

# get content of a container health report
get_container_health_report()
{
    local SERVICE=${1}

    REPORT_FILE="/data-all/${SERVICE}/health-report.json"

    if [[ -f "$REPORT_FILE" ]]; then
        REPORT_JSON=$(cat ${REPORT_FILE})
    else
        #REPORT_JSON=${REPORT_JSON:-"{}"}
        REPORT_JSON="{}"
    fi

    echo ${REPORT_JSON}

}

# prepare JSON message
messageJson=$(cat <<EOF
{
    "version":"2",
    "system_name":"${KD_SYSTEM_NAME}",
    "timestamp":"${timestamp}",
    "local_time":"${localTime}",
    "cpu_temp":"${temp}",
    "cpu_voltage":"${volts}",
    "cpu_freqency_min_mhz":"${minFreqMhz}",
    "cpu_freqency_max_mhz":"${maxFreqMhz}",
    "cpu_governor":"${governor}",
    "uptime_output":"${uptimeInfo}",
    "uptime_boot_local_time":"${uptimeBootDate}",
    "uptime_seconds":"${uptimeSeconds}",
    "disk_space_available_kb":"${availableDiskSpaceKb}",
    "disk_space_total_kb":"${totalDiskSpaceKb}",
    "services":{
        "alpr":{"is_enabled":"${KD_ALPR_ENABLED}","report":{}},
        "email_notification":{"is_enabled":"${KD_EMAIL_NOTIFICATION_ENABLED}","report":{}},
        "mqtt_bridge":{"is_enabled":"${KD_MQTT_BRIDGE_ENABLED}"},
        "ngrok":{"is_enabled":"${KD_NGROK_ENABLED}","report":$(get_container_health_report ngrok)},
        "kerberos":{"is_enabled":"${KD_KERBEROS_ENABLED}","report":$(get_container_health_report kerberos)},
        "thermometer":{"is_enabled":"${KD_THERMOMETER_ENABLED}","report":$(get_container_health_report thermometer)},
        "historian":{"is_enabled":"${KD_HISTORIAN_ENABLED}","report":$(get_container_health_report historian)},
        "health-reporter":{"is_enabled":"1","report":$(get_container_health_report health-reporter)},
        "swarm-watcher":{"is_enabled":"${KD_SWARM_WATCHER_ENABLED}","report":$(get_container_health_report swarm-watcher)},
        "file-browser":{"is_enabled":"${KD_FILEBROWSER_ENABLED}"}
    }
}
EOF
)

messageJson=$(echo ${messageJson} | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')
messageTopic="healthcheck/report"

#publish it
log_message "Attempting to publish MQTT topic $messageTopic with message $messageJson "
mosquitto_pub -h mqtt-server --retain -t "$messageTopic" -m "$messageJson"
check_errors $?

#save in local file
reportFile="/mydata/system-health-report.json"
log_message "Saving SYSTEM health report to file ${reportFile} , report = ${messageJson}"
echo ${messageJson} > ${reportFile}
check_errors $?

# prepare health report
timestamp=$(date +%s)
localTime=$(date '+%Y-%m-%d %H:%M:%S')
messageJson=$(cat <<EOF
{
    "timestamp":"${timestamp}",
    "local_time":"${localTime}"
}
EOF
)

#save health report
reportFile="/mydata/health-report.json"
messageJson=$(echo ${messageJson} | sed -z 's/\n/ /g' | sed -z 's/"/\"/g')
log_message "Saving container health report to file ${reportFile} , report = ${messageJson}"
echo "${messageJson}" > ${reportFile}

#set success flag for the container health reporter
touch /tmp/health-reporter-success.flag
check_errors $?
