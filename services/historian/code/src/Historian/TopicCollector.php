<?php


namespace Historian;

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
    private $localSystemName;

    /**
     *
     * @param Client $mqttSubscriber
     * @param string $localSystemName
     */
    function __construct(Client $mqttSubscriber, string $localSystemName)
    {
        $this->mqttClient = $mqttSubscriber;
        $this->mqttClient->onSubscribe([$this, "onSubscribe"]);
        $this->mqttClient->onConnect([$this, "onConnect"]);
        $this->mqttClient->onDisconnect([$this, "onDisconnect"]);
        $this->mqttClient->onMessage([$this, "onMessage"]);

        $this->localSystemName = $localSystemName;
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
        $this->mqttClient->subscribe('#', 2);
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


    }
}
