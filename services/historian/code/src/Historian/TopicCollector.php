<?php


namespace Historian;

use Mosquitto\Client;
use Mosquitto\Message;

/**
 *
 * @TODO remove old events
 *
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

        $this->initDatabase();
    }

    private function initDatabase()
    {
        //init database
        $stmt = "
            CREATE TABLE IF NOT EXISTS mqtt_events (
                timestamp  DATETIME DEFAULT CURRENT_TIMESTAMP,
                topic TEXT NOT NULL,
                topic1 TEXT NULL,
                topic2 TEXT NULL,
                topic3 TEXT NULL,
                topic4 TEXT NULL,
                topic5 TEXT NULL,
                payload BLOB NOT NULL
            )
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query ".json_encode($stmt)." , error = ".json_encode($this->pdo->errorInfo()));
        }

        $stmt = "
            CREATE INDEX IF NOT EXISTS index_timestamp_order
            ON mqtt_events(timestamp);
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query ".json_encode($stmt)." , error = ".json_encode($this->pdo->errorInfo()));
        }

        $stmt = "
            CREATE INDEX IF NOT EXISTS index_topic
            ON mqtt_events(topic,topic1,topic2,topic3,topic4,topic5);
        ";
        if ($this->pdo->exec($stmt) === false) {
            throw new \Exception("Cannot execute query ".json_encode($stmt)." , error = ".json_encode($this->pdo->errorInfo()));
        }
    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        echo "[".date("Y-m-d H:i:s")."][".basename(__CLASS__)."] ".$msg."\n";
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
        $this->log("received topic '".$message->topic."' with payload: '".$message->payload."'");

        //record only */thermometer/*/reading (remote) OR thermometer/*/reading (local)
        if (preg_match("|[^a-z0-9]?thermometer/[^/]*/reading$|i", $message->topic)) {

            $payload = json_decode($message->payload, true);
            if (empty($payload['timestamp'])) {

                $this->log("WARNING: payload is missing timestamp and will not be saved. topic '".$message->topic."' , payload: '".$message->payload."'");

            } else {

                $topicParts = explode("/", $message->topic);

                //save to db, if an event has timestamp+topic already recorded then it will replaced with new payload
                $sql = "
                    REPLACE INTO 
                    mqtt_events(timestamp, topic, topic1, topic2, topic3, topic4, topic5,payload) 
                    values(:timestamp,:topic,:topic1,:topic2,:topic3,:topic4,:topic5,:payload)
                ";

                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute(
                    [
                        ":timestamp" => $payload['timestamp'],
                        ":topic" => $message->topic,
                        ":topic1" => $topicParts[0] ?? null,
                        ":topic2" => $topicParts[1] ?? null,
                        ":topic3" => $topicParts[2] ?? null,
                        ":topic4" => $topicParts[3] ?? null,
                        ":topic5" => $topicParts[4] ?? null,
                        ":payload" => gzcompress($message->payload, 9),
                    ]
                );
                if ($result !== true) {
                    $this->log("WARNING: Cannot execute query ".json_encode($sql)." , error = ".json_encode($this->pdo->errorInfo()));
                }
                $this->log("successfully saved data for topic '".$message->topic."'");
            }


        } else {

            $this->log("ignoring topic '".$message->topic."'");

        }


    }


}
}
