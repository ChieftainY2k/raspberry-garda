<?php


namespace Historian;

use Mosquitto\Client;
use Mosquitto\Message;

/**
 *
 * @TODO remove old events
 *
 */
class GarbageCollector
{

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
     * @param string $localSystemName
     * @param \PDO $pdo
     * @throws \Exception
     */
    function __construct(string $localSystemName, \PDO $pdo)
    {
        $this->localSystemName = $localSystemName;
        $this->pdo = $pdo;

    }

    /**
     * @param $msg
     */
    public function log($msg)
    {
        echo "[".date("Y-m-d H:i:s")."][".basename(__CLASS__)."] ".$msg."\n";
    }

    /**
     *
     */
    public function run()
    {
        $this->log("starting garbage collection");

        $sql = "
                DELETE FROM  
                    mqtt_events 
                WHERE
                    timestamp < :timestampThreshold
        ";

        $this->log("removing old log entries...");
        //remove old entries
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(
            [
                ":timestampThreshold" => time() - (3600 * 24 * 14),
            ]
        );

        if ($result !== true) {
            $this->log("WARNING: Cannot execute query ".json_encode($sql)." , error = ".json_encode($this->pdo->errorInfo()));
        }

        $deletedRowsCount = $stmt->rowCount();

        $this->log("old entries successfully removed. deletedRowsCount = $deletedRowsCount");
    }

}
