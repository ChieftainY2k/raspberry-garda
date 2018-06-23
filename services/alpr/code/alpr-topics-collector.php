<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 */

//@TODO this is just MVP/PoC, refactor it !


require('vendor/autoload.php');

if (intval(getenv("KD_ALPR_ENABLED")) != 1) {
    echo "[" . date("Y-m-d H:i:s") . "] WARNING: ALPR service is DISABLED, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

if (
    empty(getenv("KD_ALPR_ENABLED"))
    or empty(getenv("KD_ALPR_COUNTRY"))
) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(60*15);
    exit;
}

//@TODO make it shared
$clientId = basename(__FILE__) . "-" . uniqid("");
$localQueueDirName = "/data/topics-queue";

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

$client->onMessage(function (Mosquitto\Message $message) use ($localQueueDirName) {

    echo "[" . date("Y-m-d H:i:s") . "] received topic '" . $message->topic . "' with payload: '" . $message->payload . "'\n";

    if ($message->topic == "kerberos/machinery/detection/motion") {

        //motion detected

        //@FIXME save queue files in a 1-2 level deep dir structure for faster processing ?
        //save message to local queue, repack it
        $filePath = $localQueueDirName . "/" . (microtime(true)) . ".json";
        if (!file_put_contents($filePath, json_encode([
            "timestamp" => time(),
            "topic" => $message->topic,
            "payload" => json_decode($message->payload),
        ]), LOCK_EX)) {
            throw new \Exception("Cannot save data to file " . $filePath);
        }
        echo "[" . date("Y-m-d H:i:s") . "] saved to queue file $filePath\n";

    }

});

//connect to the mqtt server, listen for topics
$client->connect("mqtt-server", 1883, 60);
$client->subscribe('kerberos/machinery/detection/motion', 2);
$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
