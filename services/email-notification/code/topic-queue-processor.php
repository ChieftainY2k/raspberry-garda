<?php
/**
 * Topics processor to aggregate collected topics into an email
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

try {

    //init
    echo "[" . date("Y-m-d H:i:s") . "] starting topic queue processing.\n";
    require(__DIR__ . "/bootstrap.php");

    //queue root path
    $topicQueuePath = "/mydata/topics-queue";
    $emailQueuePath = "/mydata/email-queues/default";
    $lastHealthReportFile = "/tmp/system-last-health-report.json";
    $pathToCapturedImages = "/etc/opt/kerberosio/capture";

    //queue processor
    $queueProcessor = new \EmailNotifier\TopicQueueProcessor($topicQueuePath, $emailQueuePath, $lastHealthReportFile, $pathToCapturedImages);
    $queueProcessor->processTopicQueue();

    echo "[" . date("Y-m-d H:i:s") . "] finished topic queue processing.\n";

} catch (Exception $e) {

    echo "[" . date("Y-m-d H:i:s") . "] EXCEPTION: " . $e . ". \n\nSleeping for a while...\n";
    sleep(60 * 10);

}

