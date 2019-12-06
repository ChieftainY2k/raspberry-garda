<?php


namespace EmailNotifier;

use Mosquitto\Client;
use Mosquitto\Message;

/**
 *
 * @use logger object
 */
class TopicCollector
{

    /**
     * @var Client
     */
    private $mqttClient;

    /**
     * @var string
     */
    private $emailQueueRootPath;

    /**
     *
     * @param Client $mqttSubscriber
     * @param string $emailQueueRootPath
     */
    function __construct(Client $mqttSubscriber, string $emailQueueRootPath)
    {
        $this->mqttClient = $mqttSubscriber;
        $this->mqttClient->onSubscribe([$this, "onSubscribe"]);
        $this->mqttClient->onConnect([$this, "onConnect"]);
        $this->mqttClient->onDisconnect([$this, "onDisconnect"]);
        $this->mqttClient->onMessage([$this, "onMessage"]);

        $this->emailQueueRootPath = $emailQueueRootPath;
    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        echo "[" . date("Y-m-d H:i:s") . "][" . basename(__CLASS__) . "] " . $msg . "\n";
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
        $localQueueDirName = "/mydata/topics-queue";

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
            $filePathTmp = $filePath . ".tmp";
            //@TODO use DTO here
            if (!file_put_contents($filePathTmp, json_encode([
                "timestamp" => time(),
                "topic" => $message->topic,
                "payload" => json_decode($message->payload),
            ]), LOCK_EX)) {
                throw new \Exception("Cannot save data to file " . $filePath);
            }

            //rename temporaty file to dest file
            if (!rename($filePathTmp, $filePath)) {
                throw new \Exception("Cannot rename file $filePathTmp to $filePath");
            }

            $this->log("saved to queue file $filePath");

        } elseif ($message->topic == "healthcheck/report") {

            //health report updated

            $lastHealthReportFile = "/tmp/system-last-health-report.json";
            $lastHealthReportFileTmp = $lastHealthReportFile . ".tmp";
            //@TODO use DTO here
            if (!file_put_contents($lastHealthReportFileTmp, json_encode([
                "timestamp" => time(),
                "topic" => $message->topic,
                "payload" => json_decode($message->payload),
            ]), LOCK_EX)) {
                throw new \Exception("Cannot save data to file " . $lastHealthReportFile);
            }

            //rename temporaty file to dest file
            if (!rename($lastHealthReportFileTmp, $lastHealthReportFile)) {
                throw new \Exception("Cannot rename file $lastHealthReportFile to $lastHealthReportFile");
            }

            $this->log("health report saved file $lastHealthReportFile");

        }


    }
}