<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 * @TODO this is just MVP/PoC, refactor it !
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting topics collector.\n";
require(__DIR__ . "/bootstrap.php");

if (intval(getenv("KD_EMAIL_NOTIFICATION_ENABLED")) != 1) {
    echo "[" . date("Y-m-d H:i:s") . "] WARNING: Email notification service is DISABLED, sleeping and exiting. To enable this service set KD_EMAIL_NOTIFICATION_ENABLED=1\n";
    sleep(60 * 15);
    exit;
}

//init mqtt client
$clientId = basename(__FILE__) . "-" . uniqid("");
echo "[" . date("Y-m-d H:i:s") . "] starting the mqtt client, clientId = $clientId\n";
$client = new Mosquitto\Client($clientId);

//init topics collector with the mqtt client
$topicsCollector = new \EmailNotifier\TopicCollector($client);

//connect to the mqtt server, listen for topics
$client->connect("mqtt-server", 1883, 60);
$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
