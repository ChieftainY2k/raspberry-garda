<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 */

//@TODO this is just MVP/PoC, refactor it !


require('vendor/autoload.php');

//@TODO make it shared
$clientId = basename(__FILE__) . "-" . uniqid("");
//$clientId = "mqtt-listener-mailer-php";
$lastHealthReportFile = "/tmp/health-report.json";
$localQueueDirName = "/mqtt-topics-queue";

if (!file_exists($localQueueDirName)) {
    if (!mkdir($localQueueDirName)) {
        throw new \Exception("Cannot create dir $localQueueDirName");
    }
}

echo "[" . date("Y-m-d H:i:s") . "] starting the listener, clientId = $clientId\n";

$client = new Mosquitto\Client($clientId);

$client->onConnect(function ($responseCode, $responseMessage) {
    echo "[" . date("Y-m-d H:i:s") . "] connected, got code $responseCode , message '$responseMessage'\n";
});

$client->onDisconnect(function () {
    echo "[" . date("Y-m-d H:i:s") . "] disconnected\n";
});

$client->onSubscribe(function () {
    echo "[" . date("Y-m-d H:i:s") . "] subscribed to a topic\n";
});

$client->onMessage(function (Mosquitto\Message $message) use ($localQueueDirName, $lastHealthReportFile) {

    echo "[" . date("Y-m-d H:i:s") . "] received topic '" . $message->topic . "' with payload: '" . $message->payload . "'\n";

    if ($message->topic == "kerberos/machinery/detection/motion") {

        //motion detected

        //@FIXME save queue files in a 1-2 level deep dir structure for faster processing ?
        //save message to local queue, repack it
        $filePath = $localQueueDirName . "/" . time() . ".json";
        if (!file_put_contents($filePath, json_encode([
            "timestamp" => time(),
            "topic" => $message->topic,
            "payload" => json_decode($message->payload),
        ]), LOCK_EX)) {
            throw new \Exception("Cannot save data to file " . $filePath);
        }
        echo "[" . date("Y-m-d H:i:s") . "] saved to queue file $filePath\n";

    } elseif ($message->topic == "healthcheck/report") {

        //health report updated

        if (!file_put_contents($lastHealthReportFile, json_encode([
            "timestamp" => time(),
            "payload" => json_decode($message->payload),
        ]), LOCK_EX)) {
            throw new \Exception("Cannot save data to file " . $lastHealthReportFile);
        }
        echo "[" . date("Y-m-d H:i:s") . "] health report saved file $lastHealthReportFile\n";

    }


});

//connect to the mqtt server, listen for topics
$client->connect("mqtt-server", 1883, 60);
$client->subscribe('kerberos/machinery/detection/motion', 2);
$client->subscribe('healthcheck/report', 2);
$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
