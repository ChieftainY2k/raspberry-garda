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
    echo "[" . date("Y-m-d H:i:s") . "] WARNING: Email notification service is DISABLED, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

//check environment params
if (
    empty(getenv("KD_EMAIL_NOTIFICATION_ENABLED"))
    //or empty(getenv("KD_REMOTE_SMTP_HOST"))
    //or empty(getenv("KD_REMOTE_SMTP_USERNAME"))
    //or empty(getenv("KD_REMOTE_SMTP_PASSWORD"))
    //or empty(getenv("KD_REMOTE_SMTP_SECURE_METHOD"))
    //or empty(getenv("KD_REMOTE_SMTP_PORT"))
    //or empty(getenv("KD_REMOTE_SMTP_FROM"))
    //or empty(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"))
    or empty(getenv("KD_SYSTEM_NAME"))
) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

$clientId = basename(__FILE__) . "-" . uniqid("");
echo "[" . date("Y-m-d H:i:s") . "] starting the listener, clientId = $clientId\n";
$client = new Mosquitto\Client($clientId);

//init topics collector
$topicsCollector = new \EmailNotifier\TopicsCollector($client);

//connect to the mqtt server, listen for topics
$client->connect("mqtt-server", 1883, 60);
$client->subscribe('kerberos/motiondetected', 2);
$client->subscribe('healthcheck/report', 2);
$client->loopForever();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
