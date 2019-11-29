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
     * @var \PDO
     */
    private $pdo;

    /**
     *
     * @param Client $mqttSubscriber
     * @param string $localSystemName
     * @param \PDO $pdo
     * @throws \Exception
     */
    function __construct(Client $mqttSubscriber, string $localSystemName, \PDO $pdo)
    {
        $this->mqttClient = $mqttSubscriber;
        $this->mqttClient->onSubscribe([$this, "onSubscribe"]);
        $this->mqttClient->onConnect([$this, "onConnect"]);
        $this->mqttClient->onDisconnect([$this, "onDisconnect"]);
        $this->mqttClient->onMessage([$this, "onMessage"]);

        $this->localSystemName = $localSystemName;
        $this->pdo = $pdo;

        //init database
        $stmt = "
            CREATE TABLE IF NOT EXISTS mqtt_events (
                timestamp  DATETIME DEFAULT CURRENT_TIMESTAMP,
                topic TEXT NOT NULL,
                payload TEXT NOT NULL
            )
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query " . json_encode($stmt) . " , error = " . json_encode($this->pdo->errorInfo()));
        }

        $stmt = "
            CREATE UNIQUE INDEX IF NOT EXISTS index_timestamp
            ON mqtt_events(timestamp, payload);
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query " . json_encode($stmt) . " , error = " . json_encode($this->pdo->errorInfo()));
        }

        $stmt = "
            CREATE INDEX IF NOT EXISTS index_timestamp
            ON mqtt_events(timestamp);
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query " . json_encode($stmt) . " , error = " . json_encode($this->pdo->errorInfo()));
        }

        $stmt = "
            CREATE INDEX IF NOT EXISTS index_topic
            ON mqtt_events(topic);
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query " . json_encode($stmt) . " , error = " . json_encode($this->pdo->errorInfo()));
        }

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

        $payload = json_decode($message->payload, true);

        //event timestamp when it was originally created
        if (empty($payload['timestamp'])) {

            $this->log("WARNING: payload without timestamp. topic '" . $message->topic . "' , payload: '" . $message->payload . "'");

        } else {

            //save to db, if an event has timestamp+topic already recorded then it will replaced with new payload
            $sql = "REPLACE INTO mqtt_events(timestamp, topic,payload) values(:timestamp,:topic,:payload)";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ":timestamp" => $payload['timestamp'],
                ":topic" => $message->topic,
                ":payload" => $message->payload
            ]);
            if ($result !== true) {
                $this->log("WARNING: Cannot execute query " . json_encode($sql) . " , error = " . json_encode($this->pdo->errorInfo()));
            }

        }


    }
}
