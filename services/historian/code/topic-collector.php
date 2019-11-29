<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting topics collector.\n";
require(__DIR__ . "/bootstrap.php");

//init mqtt client
$clientId = basename(__FILE__) . "-" . uniqid("");
echo "[" . date("Y-m-d H:i:s") . "] starting the mqtt client, clientId = $clientId\n";
$client = new Mosquitto\Client($clientId);

if (empty(getenv("KD_SYSTEM_NAME"))) {
    throw new \Exception("Empty environment variable KD_SYSTEM_NAME");
}

$localSystemName = getenv("KD_SYSTEM_NAME");

$databaseFile = "/data-historian/mqtt-history.sqlite";


//@TODO use db adapter layer, not PDO directly
$pdo = new \PDO("sqlite:" . $databaseFile);
if (empty($pdo)) {
    throw new Exception("Cannot create PDO instance");
}

//init topics collector with the mqtt client
$topicsCollector = new \Historian\TopicCollector($client, $localSystemName, $pdo);

//connect to the mqtt server, listen for topics
$client->connect("mqtt-server", 1883, 60);
$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
