<?php
/**
 * A simple MQTT subscriber
 */

require('vendor/autoload.php');

///**
// * MQTT message processor
// * @param string $topic
// * @param string $message
// */
//function processMqttMessage($topic, $message)
//{
//    echo "[" . date("Y-m-d H:i:s") . "] received topic: " . $topic . " , message: " . $message . "\n";
//}
//
//echo "[" . date("Y-m-d H:i:s") . "] starting mqtt php listener\n";
//
//$server = "mqtt-server";     // change if necessary
//$port = 1883;                     // change if necessary
//$username = "";                   // set your username
//$password = "";                   // set your password
//$client_id = "listener-mailer-php-".uniqid(""); // make sure this is unique for connecting to sever - you could use uniqid()
//
//$mqtt = new \Bluerhinos\phpMQTT($server, $port, $client_id);
//if (!$mqtt->connect(true, NULL, $username, $password)) {
//    throw new \Exception("Cannot connect to the MQTT server.");
//}
//
//$mqtt->debug = true;
//
////subscribe to all topics
//$topics['kios/detection/motion'] = [
//    "qos" => 2,
//    "function" => "processMqttMessage"
//];
//$mqtt->subscribe($topics, 0);
//
////main loop
//while ($mqtt->proc()) {
//}
//
//$mqtt->close();
//


