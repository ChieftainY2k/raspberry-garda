<?php
/**
 * UI for reports
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

//echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] Starting UI interface.\n";
require(__DIR__."/bootstrap.php");

//queue root path
$collectedHealthReportsRootPath = "/mydata/collected-health-reports";

//init topics collector with the mqtt client
$webInterface = new \SwarmWatcher\WebInterface($collectedHealthReportsRootPath);
echo $webInterface->getSwarmReportsAsWebPage();

//echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] finished.\n";

