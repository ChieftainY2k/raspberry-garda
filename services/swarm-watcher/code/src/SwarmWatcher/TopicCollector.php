<?php


namespace SwarmWatcher;

use Mosquitto\Client;
use Mosquitto\Message;
use Psr\Log\LoggerInterface;

/**
 *
 * @TODO use report saver object
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
    private $collectedHealthReportsRootPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param Client $mqttSubscriber
     * @param string $collectedHealthReportsRootPath
     * @param LoggerInterface $logger
     */
    function __construct(Client $mqttSubscriber, string $collectedHealthReportsRootPath, LoggerInterface $logger)
    {
        $this->mqttClient = $mqttSubscriber;
        $this->mqttClient->onSubscribe([$this, "onSubscribe"]);
        $this->mqttClient->onConnect([$this, "onConnect"]);
        $this->mqttClient->onDisconnect([$this, "onDisconnect"]);
        $this->mqttClient->onMessage([$this, "onMessage"]);

        $this->collectedHealthReportsRootPath = $collectedHealthReportsRootPath;

        $this->logger = $logger;
    }

    /**
     *
     */
    function onSubscribe()
    {
        $this->logger->debug("subscribed to a topic");
    }

    /**
     * @param $responseCode
     * @param $responseMessage
     */
    function onConnect($responseCode, $responseMessage)
    {
        $this->logger->debug("connected, got code $responseCode , message '$responseMessage'");

        //subscribe to remote topics with healthchecks from the gardas connected to the swarm
        $this->mqttClient->subscribe('remote/+/healthcheck/report', 2);
        $this->mqttClient->subscribe('healthcheck/report', 2);

    }

    /**
     *
     */
    function onDisconnect()
    {
        $this->logger->debug("disconnected");
    }

    /**
     * @param Message $message
     * @throws \Exception
     */
    function onMessage(Message $message)
    {
        $this->logger->debug("received topic '".$message->topic."' with payload: '".$message->payload."'");

        //ignore incoming looping back remote message about this garda
        //(happens on containers restart when local mqtt server connects to remore mqtt server via mqtt bridge)
        if ($message->topic == "remote/".getenv("KD_SYSTEM_NAME")."/healthcheck/report") {
            $this->logger->debug("topic '".$message->topic."' is ignored.");
            return;
        }

        //save topic do the dedicated file
        $filePath = $this->collectedHealthReportsRootPath."/".(md5($message->topic)).".json";
        $filePathTmp = $filePath.".tmp";
        //@TODO use DTO here
        if (!file_put_contents(
            $filePathTmp,
            json_encode(
                [
                    "timestamp" => time(),
                    "topic" => $message->topic,
                    "payload" => json_decode($message->payload),
                ]
            ),
            LOCK_EX
        )) {
            throw new \RuntimeException("Cannot save data to file ".$filePath);
        }

        //rename temporaty file to dest file
        if (!rename($filePathTmp, $filePath)) {
            throw new \RuntimeException("Cannot rename file $filePathTmp to $filePath");
        }

        $this->logger->debug("saved ".$message->topic." data to file $filePath");

    }
}