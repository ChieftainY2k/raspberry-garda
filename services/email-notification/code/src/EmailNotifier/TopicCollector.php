<?php


namespace EmailNotifier;

use Mosquitto\Client;
use Mosquitto\Message;

class TopicCollector
{

    /**
     * @var Client
     */
    private $mqttClient;

    /**
     *
     * @param Client $mqttSubscriber
     */
    function __construct(Client $mqttSubscriber)
    {
        $this->mqttClient = $mqttSubscriber;
        $this->mqttClient->onSubscribe([$this, "onSubscribe"]);
        $this->mqttClient->onConnect([$this, "onConnect"]);
        $this->mqttClient->onDisconnect([$this, "onDisconnect"]);
        $this->mqttClient->onMessage([$this, "onMessage"]);
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
        $this->log("connected, got code $responseCode , message '$responseMessage'");

        //subscribe to topics
        $this->mqttClient->subscribe('kerberos/motiondetected', 2);
        $this->mqttClient->subscribe('healthcheck/report', 2);

    }

    /**
     *
     */
    function onDisconnect()
    {
        $this->log("disconnected");
    }

    /**
     * @param Message $message
     * @throws \Exception
     */
    function onMessage(Message $message)
    {
        $this->log("received topic '" . $message->topic . "' with payload: '" . $message->payload . "'");

        //@TODO make it as construcor params or SPL file/dir for better testing
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
            $this->log("saved to queue file $filePath");

        } elseif ($message->topic == "healthcheck/report") {

            //health report updated

            if (!file_put_contents($lastHealthReportFile, json_encode([
                "timestamp" => time(),
                "topic" => $message->topic,
                "payload" => json_decode($message->payload),
            ]), LOCK_EX)) {
                throw new \Exception("Cannot save data to file " . $lastHealthReportFile);
            }
            $this->log("health report saved file $lastHealthReportFile");

        }


    }
}