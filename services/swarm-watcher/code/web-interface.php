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
$localCacheRootPath = "/mydata/cache";

//init topics collector with the mqtt client
$reportAnalyzer = new \SwarmWatcher\WebInterface($collectedHealthReportsRootPath,  $localCacheRootPath);
$reportAnalyzer->showReportsAsWebPage();

//echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] finished.\n";

