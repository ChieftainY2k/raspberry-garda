<?php


namespace EmailNotifier;

use Mosquitto\Client;
use Mosquitto\Message;

class TopicsCollector
{
    /**
     *
     * @param Client $mqttSubscriber
     */
    function __construct(Client $mqttSubscriber)
    {
        $mqttSubscriber->onSubscribe([$this, "onSubscribe"]);
        $mqttSubscriber->onConnect([$this, "onConnect"]);
        $mqttSubscriber->onDisconnect([$this, "onDisconnect"]);
        $mqttSubscriber->onMessage([$this, "onMessage"]);
    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        echo "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n";
    }

    /**
     *
     */
    function onSubscribe()
    {
        $this->log("subscribed to a topic");
    }

    /**
     * @param $responseCode
     * @param $responseMessage
     */
    function onConnect($responseCode, $responseMessage)
    {
        $this->log("[" . date("Y-m-d H:i:s") . "] connected, got code $responseCode , message '$responseMessage'");
    }

    /**
     *
     */
    function onDisconnect()
    {
        $this->log("[" . date("Y-m-d H:i:s") . "] disconnected");
    }

    /**
     * @param Message $message
     * @throws \Exception
     */
    function onMessage(Message $message)
    {
        $this->log("[" . date("Y-m-d H:i:s") . "] received topic '" . $message->topic . "' with payload: '" . $message->payload . "'");

        $lastHealthReportFile = "/tmp/health-report.json";
        $localQueueDirName = "/data/topics-queue";

        if (!file_exists($localQueueDirName)) {
            if (!mkdir($localQueueDirName)) {
                throw new \Exception("Cannot create dir $localQueueDirName");
            }
        }

        if ($message->topic == "kerberos/motiondetected") {

            //motion detected

            //@FIXME save queue files in a 1-2 level deep dir structure for faster processing ?
            //save message to local queue, repack it
            $filePath = $localQueueDirName . "/" . (microtime(true)) . ".json";
            if (!file_put_contents($filePath, json_encode([
                "timestamp" => time(),
                "topic" => $message->topic,
                "payload" => json_decode($message->payload),
            ]), LOCK_EX)) {
                throw new \Exception("Cannot save data to file " . $filePath);
            }
            $this->log("[" . date("Y-m-d H:i:s") . "] saved to queue file $filePath");

        } elseif ($message->topic == "healthcheck/report") {

            //health report updated

            if (!file_put_contents($lastHealthReportFile, json_encode([
                "timestamp" => time(),
                "payload" => json_decode($message->payload),
            ]), LOCK_EX)) {
                throw new \Exception("Cannot save data to file " . $lastHealthReportFile);
            }
            $this->log("[" . date("Y-m-d H:i:s") . "] health report saved file $lastHealthReportFile");

        }


    }
}