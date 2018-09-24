<?php
/**
 * MQTT listener to watch for topics
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the analyzer).
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting topics collector.\n";
require(__DIR__ . "/bootstrap.php");

////init mqtt client
//$clientId = basename(__FILE__) . "-" . uniqid("");
//echo "[" . date("Y-m-d H:i:s") . "] starting the mqtt client, clientId = $clientId\n";
//$client = new Mosquitto\Client($clientId);
//
////queue root path
//$topicQueuePath = "/data/email-queues";
//
////init topics collector with the mqtt client
//$topicsCollector = new \EmailNotifier\TopicCollector($client,$topicQueuePath);
//
////connect to the mqtt server, listen for topics
//$client->connect("mqtt-server", 1883, 60);
//$client->loopForever();
//
//echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
