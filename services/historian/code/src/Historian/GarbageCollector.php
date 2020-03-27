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

    public function run()
    {
        $this->log("starting garbage collection");
    }

}
