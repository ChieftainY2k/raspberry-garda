<?php
/**
 * MQTT listener to watch for "motion detected" kerbaros alerts
 *
 * This script takes incoming messages and saves them in a simple queue for further processing (by the queue processor).
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting garbage collector.\n";
require(__DIR__ . "/bootstrap.php");

if (empty(getenv("KD_SYSTEM_NAME"))) {
    throw new \Exception("Empty environment variable KD_SYSTEM_NAME");
}

$localSystemName = getenv("KD_SYSTEM_NAME");

$databaseFile = "/mydata/mqtt-history.sqlite";

//@TODO use db adapter layer, not PDO directly
$pdo = new \PDO("sqlite:" . $databaseFile);
if (empty($pdo)) {
    throw new Exception("Cannot create PDO instance");
}

//init topics collector with the mqtt client
$garbageCollector = new \Historian\GarbageCollector($localSystemName, $pdo);
$garbageCollector->run();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
