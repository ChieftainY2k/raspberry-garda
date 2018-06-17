<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing.
 *
 * Queued events are then processed, all events are grouped together and mailed to a given email address.
 *
 */

require('vendor/autoload.php');

$clientId = "mqtt-listener-mailer-php-" . uniqid("");
//create queue dir
$localQueueDirName = "/mqtt-topics-queue";
if (!file_exists($localQueueDirName)) {
    if (!mkdir($localQueueDirName)) {
        throw new \Exception("Cannot create dir $localQueueDirName");
    }
}


echo "[" . date("Y-m-d H:i:s") . "] clientId = $clientId.\n";


echo "[" . date("Y-m-d H:i:s") . "] starting the listener.\n";
$client = new Mosquitto\Client($clientId);
$client->onConnect('connect');
$client->onDisconnect('disconnect');
$client->onSubscribe('subscribe');
$client->onMessage('handleMessage');
$client->connect("mqtt-server", 1883, 60);
$client->subscribe('kerberos/machinery/detection/motion', 2);


while (true) {
    $client->loop();
    sleep(2);
}

$client->disconnect();
unset($client);

function connect($responseCode, $responseMessage)
{
    echo "[" . date("Y-m-d H:i:s") . "] connected, got code $responseCode , message '$responseMessage'\n";
}

function subscribe()
{
    echo "[" . date("Y-m-d H:i:s") . "] subscribed to a topic\n";
}

function handleMessage(Mosquitto\Message $message)
{
    global $localQueueDirName;

    echo "[" . date("Y-m-d H:i:s") . "] received topic " . $message->topic . " with payload: " . $message->payload . "\n";

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

}

function disconnect()
{
    echo "[" . date("Y-m-d H:i:s") . "] disconnected\n";
}

