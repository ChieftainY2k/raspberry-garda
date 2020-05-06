<?php
/**
 * MQTT listener to watch for topics
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the analyzer).
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


require(__DIR__ . "/bootstrap.php");

//init logger
$logger = new Logger('topicCollector');
$logger->pushHandler(new StreamHandler("php://stdout", Logger::DEBUG));

$logger->info("starting the topics collector.");

//init mqtt client
$mqttClientId = basename(__FILE__) . "-" . uniqid("");
//echo "[" . date("Y-m-d H:i:s") . "] starting the mqtt client, clientId = $mqttClientId\n";
$mqttClient = new Mosquitto\Client($mqttClientId);

//queue root path
$collectedHealthReportsRootPath = "/mydata/collected-health-reports";

//init $loggertopics collector with the mqtt client
$topicsCollector = new \SwarmWatcher\TopicCollector($mqttClient, $collectedHealthReportsRootPath, $logger);

//connect to the mqtt server, listen for topics
$mqttClient->connect("mqtt-server", 1883, 60);
$mqttClient->loopForever();

$logger->info("topics collector finished.");

