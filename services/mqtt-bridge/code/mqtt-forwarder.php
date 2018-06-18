<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 */

//@TODO this is just MVP/PoC, refactor it !


require('vendor/autoload.php');

//$clientId = "mqtt-listener-mailer-php-" . uniqid("");
$clientId = "mqtt-forwarder";

//echo "[" . date("Y-m-d H:i:s") . "] starting the listener, clientId = $clientId\n";

$client = new Mosquitto\Client($clientId);
$clientRemote = new Mosquitto\Client($clientId . "-remote");

$client->onConnect(function ($responseCode, $responseMessage) {
    echo "[" . date("Y-m-d H:i:s") . "] LOCAL: connected, got code $responseCode , message '$responseMessage'\n";
});

$client->onDisconnect(function () {
    echo "[" . date("Y-m-d H:i:s") . "] LOCAL: disconnected\n";
});

$client->onSubscribe(function () {
    echo "[" . date("Y-m-d H:i:s") . "] LOCAL: subscribed to a topic\n";
});

$client->onMessage(function (Mosquitto\Message $message) use ($clientRemote) {
    echo "[" . date("Y-m-d H:i:s") . "] LOCAL received topic '" . $message->topic . "' with payload: '" . $message->payload . "'\n";
    $res = $clientRemote->publish($message->topic, $message->payload, 1, false);
    //$res = $clientRemote->publish("test", "test", 1, false);
});

$client->connect("mqtt-server", 1883, 60);
$client->subscribe('#', 2);


$clientRemote->onConnect(function ($responseCode, $responseMessage) {
    echo "[" . date("Y-m-d H:i:s") . "] REMOTE: connected, got code $responseCode , message '$responseMessage'\n";
});

$clientRemote->onDisconnect(function () {
    echo "[" . date("Y-m-d H:i:s") . "] REMOTE: disconnected\n";
});

$clientRemote->onSubscribe(function () {
    echo "[" . date("Y-m-d H:i:s") . "] REMOTE: subscribed to a topic\n";
});

$clientRemote->onMessage(function (Mosquitto\Message $message) {
    echo "[" . date("Y-m-d H:i:s") . "] REMOTE: received topic '" . $message->topic . "' with payload: '" . $message->payload . "'\n";
});

$clientRemote->setCredentials("umnrdqlb", "eqV04m09X_Rm");
$clientRemote->setWill("disconnected", "mqtt-forwarder", 1, false);
$clientRemote->connect("m21.cloudmqtt.com", 16794, 60);
$clientRemote->publish("test/test", "test", 1, false);

$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
