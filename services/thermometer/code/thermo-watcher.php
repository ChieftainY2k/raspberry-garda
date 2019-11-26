<?php
/**
 * Health report analyzer
 *
 * This script takes saved health reports and analyzez them for anomalies
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting temp sensors watcher.\n";
require(__DIR__ . "/bootstrap.php");


function readSensons()
{
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

        //@TODO add some format checks, report if CRC is invalid
        $temperatureCelcius = null;
        if (preg_match("/crc=[0-9a-f]{2} YES/m", $rawContent, $match)) {
            if (preg_match("/ t=([0-9]+)/m", $rawContent, $match)) {
                $temperatureCelcius = number_format($match[1] / 1000, 3, '.', '');
            }
        }

        var_dump($sensorName);
        var_dump($temperatureCelcius);

        //var_dump($match);
    }
}

readSensons();;

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";

