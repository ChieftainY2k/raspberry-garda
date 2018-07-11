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
    $topicQueuePath = "/data/topics-queue";
    $emailQueuePath = "/data/email-queues/default";

    //queue processor
    $queueProcessor = new \EmailNotifier\TopicQueueProcessor($topicQueuePath,$emailQueuePath);
    $queueProcessor->processTopicQueue();

    echo "[" . date("Y-m-d H:i:s") . "] finished topic queue processing.\n";

} catch (Exception $e) {

    echo "[" . date("Y-m-d H:i:s") . "] EXCEPTION: " . $e . ". \n";
    sleep(60 * 10);

}

