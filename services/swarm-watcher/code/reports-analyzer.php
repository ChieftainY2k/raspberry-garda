<?php
/**
 * Health report analyzer
 *
 * This script takes saved health reports and analyzez them for anomalies
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

echo "[" . date("Y-m-d H:i:s") . "] Starting topics analyzer.\n";
require(__DIR__ . "/bootstrap.php");

//queue root path
$collectedHealthReportsRootPath = "/mydata/collected-health-reports";
$myHealthReportFile = "/mydata/health-report.json";
//email queue root path
$emailQueuePath = "/data-email-notification/email-queues/default";
$localCacheRootPath = "/mydata/cache";

//init topics collector with the mqtt client
$reportAnalyzer = new \SwarmWatcher\ReportAnalyzer($collectedHealthReportsRootPath, $emailQueuePath, $localCacheRootPath,$myHealthReportFile);
$reportAnalyzer->sendNotificationsBasedOnReports();
$reportAnalyzer->updateMyHealthReport();

echo "[" . date("Y-m-d H:i:s") . "] finished.\n";
