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

    ///**
    // * @throws \Exception
    // */
    //public function sendNotificationsBasedOnReports()
    //{
    //    //get current warnings based on the health reports
    //    $currentWarnings = $this->getWarningsFromLatestHealthReports();
    //    //print_r($currentWarnings);
    //
    //    //get previous warnings
    //    $lastWarningsFilePath = $this->localCacheRootPath."/"."last-warnings.json";
    //    if (file_exists($lastWarningsFilePath)) {
    //        $previousWarnings = json_decode(file_get_contents($lastWarningsFilePath), true);
    //    } else {
    //        $previousWarnings = [];
    //    }
    //
    //    //compare current and presious warnings => find new and deleted warnings
    //    $newWarnings = $this->findNewWarnings($previousWarnings, $currentWarnings);
    //    $deletedWarnings = $this->findDeletedWarnings($previousWarnings, $currentWarnings);
    //    $this->log("new warnings: ".json_encode(array_keys($newWarnings)));
    //    $this->log("deleted warnings: ".json_encode(array_keys($deletedWarnings)));
    //
    //    //Save an email in the email queue
    //    $emailHtmlBody = "";
    //
    //    if (!empty($newWarnings)) {
    //        $emailHtmlBody .= "
    //            Swarm watcher at <b>".getenv("KD_SYSTEM_NAME")."</b> detected the following <b style='color:red'>NEW anomalies</b>:<br>
    //            <ul>
    //                <li>".join("</li><li>", $newWarnings)."</li>
    //            </ul>
    //        ";
    //    }
    //
    //    if (!empty($deletedWarnings)) {
    //        $emailHtmlBody .= "
    //            Swarm watcher at <b>".getenv("KD_SYSTEM_NAME")."</b> detected that the following <b style='color:green'>anomalies DISAPPEARED</b>:<br>
    //            <ul>
    //               <li style='text-decoration: line-through;'>".join("</li><li style='text-decoration: line-through;'>", $deletedWarnings)."</li>
    //            </ul>
    //        ";
    //    }
    //
    //    if (!empty($emailHtmlBody)) {
    //        $emailSubject = ''.getenv("KD_SYSTEM_NAME").' - swarm anomaly detected';
    //
    //        $emailHtmlBody .= "
    //            <br>
    //            System local time: ".date("Y-m-d H:i:s")."
    //        ";
    //
    //        //create email data
    //        $recipient = getenv("KD_EMAIL_NOTIFICATION_RECIPIENT");
    //        //@TODO use DTO here
    //        $emailData = [
    //            "recipients" => [
    //                $recipient,
    //            ],
    //            "subject" => $emailSubject,
    //            "htmlBody" => $emailHtmlBody,
    //        ];
    //
    //        //save email data to temporary JSON file
    //        $filePath = $this->emailQueuePath."/".(microtime(true)).".json";
    //        $filePathTmp = $filePath.".tmp";
    //        if (!file_put_contents($filePathTmp, json_encode($emailData), LOCK_EX)) {
    //            throw new \Exception("Cannot save data to file ".$filePath);
    //        }
    //
    //        //rename temporaty file to dest file
    //        if (!rename($filePathTmp, $filePath)) {
    //            throw new \Exception("Cannot rename file $filePathTmp to $filePath");
    //        }
    //
    //        $this->log("email successfully created and saved to $filePath");
    //
    //    } else {
    //
    //        $this->log("no new or deleted warnings");
    //
    //    }
    //
    //
    //    //save current warnings as the las warnings
    //    if (!file_put_contents($lastWarningsFilePath, json_encode($currentWarnings), LOCK_EX)) {
    //        throw new \Exception("Cannot save data to file ".$lastWarningsFilePath);
    //    }
    //
    //}
    //
    ///**
    // * @param $previousWarnings
    // * @param $currentWarnings
    // * @return array
    // */
    //public function findNewWarnings($previousWarnings, $currentWarnings)
    //{
    //    $newWarnings = [];
    //
    //    //@TODO optimize this
    //    //check for new entries
    //    foreach ($currentWarnings as $currentWarningId => $currentWarningData) {
    //        //this new warning does not exist in previous warnings table
    //        if (empty($previousWarnings[$currentWarningId])) {
    //            $newWarnings[$currentWarningId] = $currentWarningData;
    //        }
    //    }
    //
    //    return $newWarnings;
    //}
    //
    ///**
    // * @param $previousWarnings
    // * @param $currentWarnings
    // * @return array
    // */
    //public function findDeletedWarnings($previousWarnings, $currentWarnings)
    //{
    //    $deletedWarnings = [];
    //
    //    //check for deleted entries
    //    foreach ($previousWarnings as $previousWarningId => $previousWarningData) {
    //        if (empty($currentWarnings[$previousWarningId])) {
    //            $deletedWarnings[$previousWarningId] = $previousWarningData;
    //        }
    //    }
    //
    //    return $deletedWarnings;
    //}
    //
    //
    ///**
    // * @return array warnings table
    // * @throws \Exception
    // */
    //public function getWarningsFromLatestHealthReports()
    //{
    //
    //    //scan directory for queued topics data, sort it by name, ascending
    //    $files = scandir($this->collectedHealthReportsRootPath);
    //    if ($files === false) {
    //        throw new \Exception("Cannot open directory ".$this->collectedHealthReportsRootPath."");
    //    }
    //
    //    //warnings table with current warnings based on the report
    //    $warnings = [];
    //
    //    //scan all files in queue directory
    //    foreach ($files as $queueItemFileName) {
    //
    //        if (!preg_match('/.*\.json$/i', $queueItemFileName)) {
    //            continue;
    //        }
    //
    //        //$this->log("processing $queueItemFileName");
    //
    //        $reportDataRaw = file_get_contents($this->collectedHealthReportsRootPath."/".$queueItemFileName);
    //        if (empty($reportDataRaw)) {
    //            throw new \Exception("Cannot get content of file ".$this->collectedHealthReportsRootPath."/".$queueItemFileName);
    //        }
    //        //$this->log("content =  " . $reportData . "");
    //        $reportData = json_decode($reportDataRaw, true);
    //        //var_dump($reportData);
    //        //print_r($reportData); //exit;
    //
    //        if (empty($reportData['payload']['system_name'])) {
    //            //throw new \Exception("Invalid payload data");
    //            $this->log("WARNING: invalid payload data, skipping this report and moving on. file = ".$queueItemFileName.", raw data = ".$reportDataRaw);
    //            continue;
    //        }
    //
    //        //$tmpDir = sys_get_temp_dir();
    //        $reportTimestamp = $reportData['payload']['timestamp'];
    //        $reportLocalTime = $reportData['payload']['local_time'];
    //        $reportSystemName = $reportData['payload']['system_name'];
    //        $reportVideoStreamStatus = $reportData['payload']['video_stream'] ?? null;
    //
    //        //check report age
    //        $maxReportAge = 60 * 15;
    //        if ((time() - $reportTimestamp) > $maxReportAge) {
    //            $warningMessage = "<b>".$reportSystemName."</b>: the last health report is older than ".$maxReportAge." sec. (last seen at ".$reportLocalTime." local time)";
    //            $warningId = $reportSystemName."-old-report";
    //            $warnings[$warningId] = $warningMessage;
    //        }
    //
    //        //check jpeg stream
    //        if (strpos($reportVideoStreamStatus, "Stream #0:0: Video: mjpeg") === false) {
    //            $warningMessage = "<b>".$reportSystemName."</b>: the video stream format is invalid: <b>".$reportVideoStreamStatus."</b>";
    //            $warningId = $reportSystemName."-stream-error";
    //            $warnings[$warningId] = $warningMessage;
    //        }
    //
    //    };
    //
    //    //print_r($warnings);
    //    return $warnings;
    //
    //}


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
            $this->log("Found report gardaName = ".$reportGardaName);

            //find all <watch> data in the report, create data table with watched content
            $watchNodes = $reportNode->xpath(".//watch");
            $currentWatchDataTable = [];
            foreach ($watchNodes as $watchNode) {
                $watchId = (string)$watchNode['id'];
                //$this->log("Found watch id = ".$watchId);
                $currentWatchDataTable[$watchId] = $watchNode->saveXML();
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
                if (!empty($cachedData['watchTable'])) {
                    $previousReportWatchDataTable = $cachedData['watchTable'];
                } else {
                    $this->log("ERROR: no watchTable index in JSON data from $cacheFileName");
                }
                if (!empty($cachedData['reportXml'])) {
                    $previousReportXml = $cachedData['reportXml'];
                } else {
                    $this->log("ERROR: no reportXml index in JSON data from $cacheFileName");
                }
            }

            if (!empty($previousReportWatchDataTable)) {
                if (serialize($previousReportWatchDataTable) != serialize($currentWatchDataTable)) {
                    $this->log("NOTICE: Watch data changed for gardaName = $reportGardaName ");
                    $output[] = "Report watch changed for ".$reportGardaName."";
                    $output[] = "<hr>Current report: <div>".$reportNode->saveXML()."<div>";
                    $output[] = "<hr>Previous report: <div>".$previousReportXml."</div>";
                    //print_r($output);
                    //exit;
                } else {
                    $this->log("Report did not change.");
                }

            } else {
                $this->log("NOTICE: No last watch data for report id = $reportGardaName ");
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

            print_r($output);
            exit;

            $emailSubject = ''.getenv("KD_SYSTEM_NAME").' - swarm anomaly detected';

            $emailHtmlBody = "
                Swarm watcher at <b>".getenv("KD_SYSTEM_NAME")."</b> 
                detected changes in swarm report at ".date("Y-m-d H:i:s")."<br>
            ";
            $emailHtmlBody .= "".join("", $output)."";

            //create email data
            //@TODO use DTO here
            $recipient = getenv("KD_EMAIL_NOTIFICATION_RECIPIENT");
            $emailData = [
                "recipients" => [
                    $recipient,
                ],
                "subject" => $emailSubject,
                "htmlBody" => $emailHtmlBody,
            ];

            //save email data to temporary JSON file
            $filePath = $this->emailQueuePath."/".(microtime(true)).".json";
            $filePathTmp = $filePath.".tmp";
            if (!file_put_contents($filePathTmp, json_encode($emailData), LOCK_EX)) {
                throw new \Exception("Cannot save data to file ".$filePath);
            }

            //rename temporaty file to dest file
            if (!rename($filePathTmp, $filePath)) {
                throw new \Exception("Cannot rename file $filePathTmp to $filePath");
            }

            $this->log("email successfully created and saved to $filePath");

        }

    }

}


