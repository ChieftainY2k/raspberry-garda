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

    /**
     * @param array $report
     */
    public function showReport($report)
    {
        $payload = $report['payload'];
        $version = $payload['version'];
        $systemName = $payload['system_name'];

        echo "<b>$systemName</b>";

        if ($version == 1) {

        } elseif ($version == 2) {

        } else {
            echo "ERROR: unsupported raport payload version $version";
        }
    }

    /**
     *
     */
    public function showReportsAsWebPage()
    {

        echo "
            <html>
            <head>
                <title>Swarm Watcher (".htmlspecialchars(getenv("KD_SYSTEM_NAME")).")</title>
            </head>
            <body>
        ";

        //scan all collected report files, visualize
        $reportFiles = glob($this->collectedHealthReportsRootPath."/*.json");
        foreach ($reportFiles as $fileName) {
            echo "<div style='margin:10px; border: solid 1px black; padding:5px;'>";
            $fileContent = file_get_contents($fileName);
            if (empty($fileContent)) {
                //error
                echo "ERROR: Cannot get content from $fileName";
            } else {
                $report = json_decode($fileContent, true);
                if ($report === false) {
                    echo "ERROR: invalid json from $fileName";
                } else {
                    $this->showReport($report);
                }
            }
            echo "</div>";
        }

        echo "
            </body>
            
            </html>
        ";
    }

}


