<?php


namespace SwarmWatcher;

use Mosquitto\Client;
use Mosquitto\Message;

/**
 *
 * @TODO use logger object
 * @TODO use SPL for files and directories
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
    private $healthReportsRootPath;

    /**
     *
     * @param Client $mqttSubscriber
     * @param string $healthReportsRootPath
     */
    function __construct(Client $mqttSubscriber, string $healthReportsRootPath)
    {
        $this->mqttClient = $mqttSubscriber;
        $this->mqttClient->onSubscribe([$this, "onSubscribe"]);
        $this->mqttClient->onConnect([$this, "onConnect"]);
        $this->mqttClient->onDisconnect([$this, "onDisconnect"]);
        $this->mqttClient->onMessage([$this, "onMessage"]);

        $this->healthReportsRootPath = $healthReportsRootPath;
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

        //subscribe to remote topics with healthchecks from the gardas connected to the swarm
        $this->mqttClient->subscribe('remote/+/healthcheck/report', 2);

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

        //save topic do the dedicated file
        $filePath = $this->healthReportsRootPath . "/" . (md5($message->topic)) . ".json";
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

        $this->log("saved " . $message->topic . " data to file $filePath");


    }
}