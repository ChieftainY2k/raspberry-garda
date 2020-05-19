<?php
/**
 * Health report analyzer
 *
 * This script takes saved health reports and analyzez them for anomalies
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[".date("Y-m-d H:i:s")."] starting temp sensors watcher.\n";
require(__DIR__."/bootstrap.php");


function getSensorAlias($systemRawSensorName)
{
    $aliasesConfigJson = getenv("KD_THERMOMETER_ALIASES") ?? "[]";
    if (!empty($aliasesConfigJson)) {
        $aliasesTable = json_decode($aliasesConfigJson, true);
        if (empty($aliasesTable)) {
            echo "WARNING: invalid sensor's aliases definition. data = ".$aliasesTable."\n";
        }
    }
    $alias = $aliasesTable[$systemRawSensorName] ?? $systemRawSensorName;

    //sanitize the name
    $alias = preg_replace('/[^a-z0-9-_]/i', "_", $alias);

    //if (!preg_match('/^[a-z0-9-_]+$/i', $alias)) {
    //    echo "WARNING: invalid characters in alias name for sensor " . $systemRawSensorName . ", alias = " . $alias . "\n";
    //    $alias = $systemRawSensorName;
    //}

    return $alias;

}

function readSensors()
{
    //load the services configuration
    (new Dotenv\Dotenv("/service-configs", "services.conf"))->load();

    //mqtt client
    $mqttClientId = basename(__FILE__)."-".uniqid("");
    $mqttClient = new Mosquitto\Client($mqttClientId);
    $mqttClient->connect("mqtt-server", 1883, 60);

    $sensorsList = [];
    $sensorFiles = glob("/sys/bus/w1/devices/28*/w1_slave");
    foreach ($sensorFiles as $sensorFile) {
        $rawContent = file_get_contents($sensorFile);
        if (empty($rawContent)) {
            throw new Exception("Empty content from $sensorFile");
        }

        /*
            Content example:
            1d 01 4b 46 7f ff 0c 10 dd : crc=dd YES
            1d 01 4b 46 7f ff 0c 10 dd t=17812
        */

        $sensorName = null;
        if (preg_match("#([^/]+)/w1_slave#", $sensorFile, $match)) {
            $sensorName = $match[1];
        } else {
            throw new Exception("Unknown format of sensor file name: $sensorFile");
        }

        $sensorNameAlias = getSensorAlias($sensorName);

        //@TODO add some format checks, report if CRC is invalid
        $temperatureCelcius = null;
        if (preg_match("/crc=[0-9a-f]{2} YES/m", $rawContent, $match)) {
            if (preg_match("/ t=([0-9]+)/m", $rawContent, $match)) {
                $temperatureCelcius = number_format($match[1] / 1000, 3, '.', '');
            }
        }

        echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] ".$sensorFile." = ".json_encode($rawContent)."\n";

        $topicName = "thermometer/".$sensorNameAlias."/reading";
        $messageData = [
            "system_name" => getenv("KD_SYSTEM_NAME"),
            "timestamp" => time(),
            "local_time" => date("Y-m-d H:i:s"),
            "sensor_name" => $sensorNameAlias,
            "sensor_name_original" => $sensorName,
            "sensor_reading" => [
                "celcius" => $temperatureCelcius,
                "raw" => $rawContent,
            ],
        ];

        $mqttClient->publish($topicName, json_encode($messageData), 0, false);

        //@TODO publish event if temperature increase over given time window is over a given threshold

        //save in senrors list for service health report
        $sensorsList[] = [
            "sensor_name" => $sensorNameAlias,
            "sensor_name_original" => $sensorName,
            "sensor_reading" => [
                "celcius" => $temperatureCelcius,
                "raw" => $rawContent,
            ],
        ];

    }

    $mqttClient->disconnect();

    //save service health report
    $healthReportFile = "/mydata/health-report.json";
    $healthReportData = [
        "timestamp" => (string)time(),
        "local_time" => date("Y-m-d H:i:s"),
        "sensors" => $sensorsList,
    ];

    echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] saving health report to ".$healthReportFile." , report = ".json_encode($healthReportData)."\n";

    if (!file_put_contents($healthReportFile, json_encode($healthReportData), LOCK_EX)) {
        throw new \Exception("Cannot save data to file ".$healthReportFile);
    }

}

readSensors();

echo "[".date("Y-m-d H:i:s")."] finished.\n";

