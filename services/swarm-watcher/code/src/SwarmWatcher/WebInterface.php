<?php


namespace SwarmWatcher;


/**
 * Analyze report collection and produce readable report with new warnings and warnings that disappeared
 *
 * @TODO use logger object
 * @TODO use SPL for files and directories
 * @TODO use objects/factories for better testing
 */
class WebInterface
{

    /**
     * @var string
     */
    private $collectedHealthReportsRootPath;

    /**
     * @var string
     */
    private $localCacheRootPath;

    /**
     * ReportAnalyzer constructor.
     * @param $collectedHealthReportsRootPath string
     * @param $localCacheRootPath string
     * @throws \Exception
     */
    function __construct($collectedHealthReportsRootPath, $localCacheRootPath)
    {
        $this->collectedHealthReportsRootPath = $collectedHealthReportsRootPath;
        $this->localCacheRootPath = $localCacheRootPath;

        //@TODO validate the input
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
        echo "[".date("Y-m-d H:i:s")."][".basename(__CLASS__)."] ".$msg."\n";
    }

    public function showReportsAsWebPage()
    {

    }

}


