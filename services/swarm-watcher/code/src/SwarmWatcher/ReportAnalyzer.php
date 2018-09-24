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
     * @var string
     */
    private $localCacheRootPath;

    /**
     * ReportAnalyzer constructor.
     * @param $healthReportsRootPath string
     * @param $emailQueuePath string
     * @param $localCacheRootPath string
     * @throws \Exception
     */
    function __construct($healthReportsRootPath, $emailQueuePath, $localCacheRootPath)
    {
        $this->healthReportsRootPath = $healthReportsRootPath;
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
        echo "[" . date("Y-m-d H:i:s") . "][" . basename(__CLASS__) . "] " . $msg . "\n";
    }


    /**
     * @throws \Exception
     */
    public function sendNotificationsBasedOnReports()
    {
        //get current warnings based on the health reports
        $currentWarnings = $this->getCurrentWarnings();
        print_r($currentWarnings);

        //get previous warnings
        $lastWarningsFilePath = $this->localCacheRootPath . "/" . "last-warnings.json";
        if (file_exists($lastWarningsFilePath)) {
            $lastWarnings = json_decode(file_get_contents($lastWarningsFilePath), true);
        } else {
            $lastWarnings = [];
        }

        //print_r($lastWarnings);
        //exit;

        //save current warnings as the las warnings
        if (!file_put_contents($lastWarningsFilePath, json_encode($currentWarnings), LOCK_EX)) {
            throw new \Exception("Cannot save data to file " . $lastWarningsFilePath);
        }

    }

    /**
     * @throws \Exception
     * @return array warnings table
     */
    public function getCurrentWarnings()
    {

        //scan directory for queued topics data, sort it by name, ascending
        $files = scandir($this->healthReportsRootPath);
        if ($files === false) {
            throw new \Exception("Cannot open directory " . $this->healthReportsRootPath . "");
        }

        //warnings table with current warnings based on the report
        $warnings = [];

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
            $reportData = json_decode($reportData, true);
            //print_r($reportData); //exit;

            if (empty($reportData['payload']['system_name'])) {
                //throw new \Exception("Invalid payload data");
                $this->log("Warning: invalid payload data, skipping this report and moving on.");
                continue;
            }

            $tmpDir = sys_get_temp_dir();
            $reportTimestamp = $reportData['payload']['timestamp'];
            $reportLocalTime = $reportData['payload']['local_time'];
            $reportSystemName = $reportData['payload']['system_name'];
            $reportVideoStreamStatus = $reportData['payload']['video_stream'];

            $maxReportAge = 60 * 15;
            if ((time() - $reportTimestamp) > $maxReportAge) {
                $message = "<b>" . $reportSystemName . "</b>: the last health report is older than " . $maxReportAge . " sec. (last reported at " . $reportLocalTime . " local time)";
                $messageDeterministicId = $reportSystemName . "-old-report";
                $warnings[$messageDeterministicId] = $message;
            }

            if (strpos($reportVideoStreamStatus, "Stream #0:0: Video: mjpeg") === false) {
                $message = "<b>" . $reportSystemName . "</b>: the video stream format is invalid: <b style='color:red'>" . $reportVideoStreamStatus . "</b>";
                $messageDeterministicId = $reportSystemName . "-stream-error";
                $warnings[$messageDeterministicId] = $message;
            }

            /*
             * Array
            (
                [timestamp] => 1537790459
                [topic] => remote/DevBox/healthcheck/report
                [payload] => Array
                    (
                        [system_name] => MySurveillanceBox
                        [timestamp] => 1535280716
                        [local_time] => 2018-08-26 12:51:56
                        [cpu_temp] => 60.7
                        [cpu_voltage] => 1.20
                        [uptime_seconds] => 2944
                        [disk_space_available_kb] => 1209864
                        [disk_space_total_kb] => 7645880
                        [images_size_kb] => 857620
                        [services] => Array
                            (
                                [alpr] => 1
                                [email_notification] => 1
                                [mqtt_bridge] => 1
                            )

                        [video_stream] => http://kerberos:8889: Connection refused
                    )

            )
             */


        };

        //print_r($warnings);
        return $warnings;

    }

}
















