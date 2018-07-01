<?php

namespace EmailNotifier;

use Mosquitto\Client;
use PHPMailer\PHPMailer\PHPMailer;

class EmailQueueProcessor
{
    /**
     * @var Client
     */
    private $mqttClient;

    /**
     * @var PHPMailer
     */
    private $mailer;

    /**
     * @var string
     */
    private $queueRootPath;

    /**
     *
     * @param PHPMailer $mailer
     * @param Client $mqttClient
     * @param string $queueRootPath
     */
    function __construct(PHPMailer $mailer, Client $mqttClient, string $queueRootPath)
    {
        $this->mqttClient = $mqttClient;
        $this->mailer = $mailer;
        $this->queueRootPath = $queueRootPath;
    }

    /**
     * @param string $subQueuePath
     * @throws \Exception
     */
    function processQueue($subQueuePath = "")
    {

        //process the queue
        $dirPath = $this->queueRootPath . "/" . $subQueuePath;

        echo "[" . date("Y-m-d H:i:s") . "] starting processing directory $dirPath ...\n";

        $dirHandle = opendir($dirPath);
        if (!$dirHandle) {
            throw new \Exception("Cannot open directory " . $dirPath . "");
        }

        //scan all files in queue directory
        while (($fileName = readdir($dirHandle)) !== false) {

            if (!preg_match("/^[a-z0-9_-]+$/i", $fileName)) {
                continue;
            }

            //process a sub-directory
            if (is_dir($dirPath . "/" . $fileName)) {
                $this->processQueue($fileName);
                continue;
            }

            //process a file, ignore non-json extensions
            if (!preg_match('/.*\.json$/i', $fileName)) {
                continue;
            }

            $this->processFile($dirPath . "/" . $fileName);
        };

        echo "[" . date("Y-m-d H:i:s") . "] finished processing directory $dirPath ...\n";

    }

    /**
     * @param $filePath
     */
    function processFile($filePath)
    {
        echo "[" . date("Y-m-d H:i:s") . "] processing $filePath \n";

    }

}