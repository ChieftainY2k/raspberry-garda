<?php
/**
 * Health report analyzer
 *
 * This script takes saved health reports and analyzez them for anomalies
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting temp sensors watcher.\n";
require(__DIR__ . "/bootstrap.php");


echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
