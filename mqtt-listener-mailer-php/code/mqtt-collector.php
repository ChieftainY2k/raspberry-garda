<?php
/**
 * A simple MQTT subscriber
 */

require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting the listener.\n";
$client = new Mosquitto\Client("mqtt-listener-mailer-php-" . uniqid(""));
$client->onConnect('connect');
$client->onDisconnect('disconnect');
$client->onSubscribe('subscribe');
$client->onMessage('message');
$client->connect("mqtt-server", 1883, 60);
$client->subscribe('#', 2);


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

function message(Mosquitto\Message $message)
{
    echo "[" . date("Y-m-d H:i:s") . "] received topic " . $message->topic . " with payload: " . $message->payload . "\n";
}

function disconnect()
{
    echo "[" . date("Y-m-d H:i:s") . "] disconnected\n";
}

