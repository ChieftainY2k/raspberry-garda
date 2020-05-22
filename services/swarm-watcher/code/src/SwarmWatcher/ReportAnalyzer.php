<?php


namespace SwarmWatcher;


/**
 * Analyze report collection and produce readable report with new warnings and warnings that disappeared
 *
 * @TODO use logger object
 * @TODO use SPL for files and directories
 * @TODO use objects/factories for better testing
 */
class ReportAnalyzer
{

    /**
     * @var string
     */
    private $collectedHealthReportsRootPath;

    /**
     * @var string
     */
    private $myHealthReportFile;

    /**
     * @var string
     */
    private $emailQueuePath;

    /**
     * @var string
     */
    private $localCacheRootPath;

    /**
     * ReportAnalyzer constructor.
     * @param $collectedHealthReportsRootPath string
     * @param $emailQueuePath string
     * @param $localCacheRootPath string
     * @param $myHealthReportFile
     * @throws \Exception
     */
    function __construct($collectedHealthReportsRootPath, $emailQueuePath, $localCacheRootPath, $myHealthReportFile)
    {
        $this->collectedHealthReportsRootPath = $collectedHealthReportsRootPath;
        $this->myHealthReportFile = $myHealthReportFile;
        $this->emailQueuePath = $emailQueuePath;
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
     *
     */
    public function updateMyHealthReport()
    {
        $reportFiles = glob($this->collectedHealthReportsRootPath."/*.json");

        //save service health report
        $healthReportFile = $this->myHealthReportFile;
        $healthReportData = [
            "timestamp" => (string)time(),
            "local_time" => date("Y-m-d H:i:s"),
            "collected_report_files_count" => count($reportFiles),
        ];

        $this->log("saving health report to ".$healthReportFile." , report = ".json_encode($healthReportData)."");

        if (!file_put_contents($healthReportFile, json_encode($healthReportData), LOCK_EX)) {
            throw new \Exception("Cannot save data to file ".$healthReportFile);
        }
    }

    /**
     * Replace class="" with inline style="" so that all email readers can handle the html message
     *
     * @param $html
     * @return string|string[]
     */
    private function replaceClassWithInlineStyles($html)
    {
        //inject inline styling
        $html = str_replace('class="reportName"', 'style="font-size:15px;"', $html);
        $html = str_replace(
            'class="reportContainer"',
            '
                style="
                    font-family: Arial;
                    border: 1px solid black;
                    border-radius: 3px;
                    margin: 1px;
                    padding: 5px;
                    background: #efffff;
                    color: black;
                    font-size:11px;
                    vertical-align:top; 
                "
            ',
            $html
        );
        $html = str_replace(
            'class="service"',
            '
                style="
                    border: 1px solid #aaa;
                    border-radius: 3px;
                    margin: 1px;
                    padding: 2px;
                    background: #efefef;
                    color: black;
                    font-size:11px;
                "
            ',
            $html
        );
        $html = str_replace(
            'class="notice"',
            '
                style="
                    margin: 1px;
                    padding: 1px;
                    color: brown;
                "
            ',
            $html
        );
        $html = str_replace(
            'class="warning"',
            '
                style="
                    display: inline-block;
                    border: 1px solid #aaa;
                    border-radius: 3px;
                    margin: 1px;
                    padding: 1px;
                    background: #ffaaaa;
                    color: black;
                "
            ',
            $html
        );

        return $html;
    }

    /**
     * @param WebInterface $webInterface
     * @throws \Exception
     */
    public function analyzeSwarmWebReport(WebInterface $webInterface)
    {
        $output = [];

        $htmlTextReport = $webInterface->getSwarmReportHtml();
        if (empty($htmlTextReport)) {
            throw new \Exception("Empty html report");
        }

        //import html report to dom document and xml document
        $htmlDocument = new \DOMDocument();
        @$htmlDocument->loadHTML($htmlTextReport);
        $xmlDocument = simplexml_import_dom($htmlDocument);
        if (empty($xmlDocument)) {
            throw new \Exception("Unable to import html document to XML document");
        }

        //fint all <report> elements
        $reportNodes = $xmlDocument->xpath('.//report');
        foreach ($reportNodes as $reportNode) {
            $reportGardaName = (string)$reportNode['id'];
            if (empty($reportGardaName)) {
                $this->log("ERROR: empty id from the report XML node.");
                continue;
            }
            $this->log("Found report gardaName = ".$reportGardaName);

            //find all <watch> data in the report, create data table with watched content
            $watchNodes = $reportNode->xpath(".//watch");
            $currentWatchDataTable = [];
            foreach ($watchNodes as $watchNode) {
                $watchId = (string)$watchNode['id'];
                //$this->log("Found watch id = ".$watchId);
                $currentWatchDataTable[$watchId] = $watchNode->saveXML();
            }
            if (empty($currentWatchDataTable)) {
                $this->log("WARNING: empty currentWatchDataTable");
            }
            //print_r($currentWatchDataTable);

            //load previous data from the last run
            $previousReportWatchDataTable = null;
            $previousReportXml = null;
            $cacheFileName = $this->localCacheRootPath."/".md5($reportGardaName)."-last.json";
            if (file_exists($cacheFileName)) {
                $cachedData = null;
                $olderWatchDataTableJson = file_get_contents($cacheFileName);
                if (empty($olderWatchDataTableJson)) {
                    $this->log("ERROR: empty content from file $cacheFileName");
                }
                if (!empty($olderWatchDataTableJson)) {
                    $cachedData = json_decode($olderWatchDataTableJson, true);
                    if (empty($cachedData)) {
                        $this->log("ERROR: invalid JSON from file $cacheFileName");
                    }
                }
                if (isset($cachedData['watchTable'])) {
                    $previousReportWatchDataTable = $cachedData['watchTable'];
                } else {
                    $this->log("WARNING: no watchTable index in JSON data from $cacheFileName");
                }
                if (isset($cachedData['reportXml'])) {
                    $previousReportXml = $cachedData['reportXml'];
                } else {
                    $this->log("WARNING: no reportXml index in JSON data from $cacheFileName");
                }
            }

            //print_r($currentWatchDataTable);
            //print_r($previousReportWatchDataTable);
            //$previousReportWatchDataTable['ngrokUrl']="<watch id=\"ngrokUrl\"><a href=\"http://alamakota.7de0648d.ngrok.io\">7de0648d.ngrok.io</a></watch>";

            if (!empty($previousReportWatchDataTable)) {
                if (serialize($previousReportWatchDataTable) != serialize($currentWatchDataTable)) {
                    //if (true) {
                    $this->log("watchers data changed for gardaName = $reportGardaName ");

                    //show specific changes
                    $output[] = "<div style='margin-bottom:10px; border: 1px solid black; border-radius: 3px; padding: 5px; background: #dfdfff;'>";
                    $output[] = "
                        <div style='color: blue; font-size:14px;'>
                            <b>".$reportGardaName."</b> - report summary
                        </div>
                    ";
                    $output[] = "<ul>";
                    //show watchers that changed or appeared
                    foreach ($currentWatchDataTable as $watchId => $watchData) {
                        if (!isset($previousReportWatchDataTable[$watchId])) {
                            $this->log("watcher '".$watchId."' appeared and was not in the last report.");
                            $output[] = "
                                <li>
                                Watcher '<b>".$watchId."</b>' changed from <b>[does not exist]</b> to    
                                <span style='border:solid 1px #aaaaaa; background: yellow; padding:2px;'>".strip_tags($currentWatchDataTable[$watchId])."</span> 
                            ";
                        } elseif ($currentWatchDataTable[$watchId] != $previousReportWatchDataTable[$watchId]) {
                            $this->log("watcher '".$watchId."' changed since last report.");
                            $output[] = "
                                <li>
                                Watcher '<b>".$watchId."</b>' changed value from 
                                <span style='border:solid 1px #aaaaaa; background: yellow; padding:2px;'>".strip_tags($previousReportWatchDataTable[$watchId])."</span> 
                                to
                                <span style='border:solid 1px #aaaaaa; background: yellow; padding:2px;'>".strip_tags($currentWatchDataTable[$watchId])."</span> 
                            ";
                        }
                    }
                    //show watchers that disappeared
                    foreach ($previousReportWatchDataTable as $watchId => $watchData) {
                        if (!isset($currentWatchDataTable[$watchId])) {
                            $this->log("watcher '".$watchId."' disappeared and was in the previous report.");
                            $output[] = "
                                <li>
                                Watcher '<b>".$watchId."</b>' changed from   
                                <span style='border:solid 1px #aaaaaa; background: yellow; padding:2px;'>".strip_tags($previousReportWatchDataTable[$watchId])."</span>
                                to
                                <b>[disappeared]</b> 
                            ";
                        }
                    }
                    $output[] = "<ul>";
                    $output[] = "</div>";


                    //show general report
                    $output[] = "
                        <div style='margin-bottom:10px; border: 1px solid black; border-radius: 3px; padding: 5px; background: #dfdfdf;'>
                            <div style='color: blue; font-size:14px;'>
                                <b>".$reportGardaName."</b> - service reports 
                            </div>
                            <table border='0' cellpadding='0' cellspacing='1'>
                            <tr>
                                <td valign='top' width='45%' align='left'>
                                    <b style='font-size:12px;'>Previous report:</b>
                                    <div class=\"reportContainer\">".$previousReportXml."</div>
                                </td>
                                <td valign='top' width='20'>
                                    <b>&raquo;</b>
                                </td>
                                <td valign='top' width='45%' align='left'>
                                    <b style='font-size:12px;'>Current report:</b>
                                    <div class=\"reportContainer\">".$reportNode->saveXML()."<div>
                                </td>
                                </tr>
                            </table>
                        </div>
                    ";

                    $output[] = "<hr>";

                    //print_r($output);
                    //exit;
                } else {
                    $this->log("Report did not change.");
                }

            } else {
                $this->log("No last watch data for report id = $reportGardaName ");
            }

            //save this data to cache for the next run
            if (!file_put_contents(
                $cacheFileName,
                json_encode(
                    [
                        "raportId" => $reportGardaName,
                        "reportXml" => $reportNode->saveXML(),
                        "watchTable" => $currentWatchDataTable,
                    ]
                ),
                LOCK_EX
            )) {
                $this->log("ERROR: cannot save data to file $cacheFileName");
            }

            //break;
        }


        if (!empty($output)) {

            $emailHtmlBody = "
                <div style='font-family: Arial; font-size:12px;'>
                    <div style='padding:5px'>
                    Swarm watcher at <b>".getenv("KD_SYSTEM_NAME")."</b> 
                    detected changes in swarm report at ".date("Y-m-d H:i:s")."<br>
                    </div>
                    <div style='padding:5px'>
                    ".join("", $output)."
                    </div>
                </div>
            ";

            //create email data
            $emailDataJson = json_encode(
                [
                    "recipients" => [getenv("KD_EMAIL_NOTIFICATION_RECIPIENT")],
                    "subject" => ''.getenv("KD_SYSTEM_NAME").' - swarm anomaly detected',
                    "htmlBody" => $this->replaceClassWithInlineStyles($emailHtmlBody),
                ]
            );
            //save email data to email queue
            $filePath = $this->emailQueuePath."/".(microtime(true)).".json";
            if (!file_put_contents($filePath, $emailDataJson, LOCK_EX)) {
                throw new \Exception("Cannot save data to file ".$filePath);
            }
            $this->log("email successfully created and saved to $filePath");

        }

    }

}


