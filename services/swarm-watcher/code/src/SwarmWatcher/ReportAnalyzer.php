<?php


namespace SwarmWatcher;


/**
 *
 * @TODO use logger object
 * @TODO use SPL for files and directories
 */
class ReportAnalyzer
{

    /**
     * @var string
     */
    private $healthReportsRootPath;

    /**
     * @var string
     */
    private $emailQueuePath;

    /**
     * ReportAnalyzer constructor.
     * @param $healthReportsRootPath string
     * @param $emailQueuePath string
     * @throws \Exception
     */
    function __construct($healthReportsRootPath, $emailQueuePath)
    {
        $this->healthReportsRootPath = $healthReportsRootPath;
        $this->emailQueuePath = $emailQueuePath;

        if (empty(getenv("KD_SYSTEM_NAME"))) {
            throw new \Exception("Empty environment variable KD_SYSTEM_NAME");
        }
        if (empty(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"))) {
            throw new \Exception("Empty environment variable KD_EMAIL_NOTIFICATION_RECIPIENT");
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
     * @throws \Exception
     */
    public function analyzeReports()
    {

        //scan directory for queued topics data, sort it by name, ascending
        $files = scandir($this->healthReportsRootPath);
        if ($files === false) {
            throw new \Exception("Cannot open directory " . $this->healthReportsRootPath . "");
        }

        //scan all files in queue directory
        foreach ($files as $queueItemFileName) {

            if (!preg_match('/.*\.json$/i', $queueItemFileName)) {
                continue;
            }

            $this->log("processing $queueItemFileName");

            $reportData = file_get_contents($this->healthReportsRootPath . "/" . $queueItemFileName);
            if (empty($reportData)) {
                throw new \Exception("Cannot get content of file " . $this->healthReportsRootPath . "/" . $queueItemFileName);
            }
            $this->log("content =  " . $reportData . "");
            $reportData = json_decode($reportData);

        };


    }

}