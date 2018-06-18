<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 */

//@TODO this is just MVP/PoC, refactor it !


require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting topic forwarder.\n";

//check environment params
if (
    empty(getenv("KD_MQTT_BRIDGE_REMOTE_HOST"))
    or empty(getenv("KD_MQTT_BRIDGE_REMOTE_PORT"))
    or empty(getenv("KD_MQTT_BRIDGE_REMOTE_USER"))
    or empty(getenv("KD_MQTT_BRIDGE_REMOTE_PASSWORD"))
) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(3600);
    exit;
}


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
    echo "[" . date("Y-m-d H:i:s") . "] received topic '" . $message->topic . "' with payload: '" . $message->payload . "'\n";
    $res = $clientRemote->publish($message->topic, $message->payload, 1, false);
    echo "[" . date("Y-m-d H:i:s") . "] forwarded to REMOTE MQTT server, result = " . $res . "\n";
    //$res = $clientRemote->publish("test", "test", 1, false);
});


$client->connect("mqtt-server", 1883, 60);
$client->subscribe('#', 2);

//Init remote client
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
$clientRemote->setCredentials(getenv("KD_MQTT_BRIDGE_REMOTE_USER"), getenv("KD_MQTT_BRIDGE_REMOTE_PASSWORD"));
$clientRemote->setWill("service/disconnected/mqtt-forwarder", '{"name":"' . getenv("KD_SYSTEM_NAME") . '"}', 1, false);
$clientRemote->connect(getenv("KD_MQTT_BRIDGE_REMOTE_HOST"), getenv("KD_MQTT_BRIDGE_REMOTE_PORT"), 60);
$clientRemote->publish("service/connected/mqtt-forwarder", '{"name":"' . getenv("KD_SYSTEM_NAME") . '"}', 1, false);


while (true) {
    $client->loop();
    $clientRemote->loop();
    sleep(1);
}
//$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
