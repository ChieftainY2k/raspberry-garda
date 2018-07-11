<?php

namespace EmailNotifier;

use Mosquitto\Client;
use PHPMailer\PHPMailer\PHPMailer;

/**
 *
 * @TODO use logger object
 */
class TopicQueueProcessor
{
    /**
     *
     * @var string
     */
    private $topicQueueRootPath;

    /**
     *
     * @var string
     */
    private $emailQueueRootPath;

    /**
     *
     * @var string
     */
    private $lastHealthReportFile;

    /**
     *
     * @param string $topicQueueRootPath
     * @param string $emailQueueRootPath
     * @param string $lastHealthReportFile
     */
    function __construct(string $topicQueueRootPath, string $emailQueueRootPath, string $lastHealthReportFile)
    {
        $this->topicQueueRootPath = $topicQueueRootPath;
        $this->emailQueueRootPath = $emailQueueRootPath;
        $this->lastHealthReportFile = $lastHealthReportFile;
    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        echo "[" . date("Y-m-d H:i:s") . "][" . basename(__CLASS__) . "] " . $msg . "\n";
    }

    /**
     * @return null|string
     */
    function getLastHealthReportAsHtml()
    {
        $htmlBody = null;

        //attach last health report if available
        if (file_exists($this->lastHealthReportFile)) {

            $lastHealthReportData = file_get_contents($this->lastHealthReportFile);
            $lastHealthReportData = json_decode($lastHealthReportData, true);
            $reportPayload = $lastHealthReportData['payload'];
            $uptimeSeconds = $reportPayload['uptime_seconds'];
            $htmlBody .= "
        <br><br>Last health report (reported " . date("Y-m-d H:i:s", $reportPayload['timestamp']) . "): <br>
        <ul>
            <li>System name: <b>" . $reportPayload['system_name'] . "</b></li>
            <li>Uptime: <b>" . floor($uptimeSeconds / 3600) . "h " . gmdate("i", $uptimeSeconds % 3600) . "m</b></li>
            <li>CPU: <b>" . $reportPayload['cpu_temp'] . "'C</b> , <b>" . $reportPayload['cpu_voltage'] . "V</b></li>
            <li>Disk: 
                <b>" . number_format($reportPayload['disk_space_available_kb'] / 1024 / 2014, 2) . " GB
                (" . number_format(100 * ($reportPayload['disk_space_available_kb'] / $reportPayload['disk_space_total_kb']), 2) . "%) </b> available ,
                <b>" . number_format($reportPayload['disk_space_total_kb'] / 1024 / 1024, 2) . " GB </b> 
                total 
                
            </li>
        </ul>";
            //<li>Uptime: <b>" . gmdate("H:i:s", $reportPayload['uptime_seconds']) . " (hours:minutes:seconds)</b></li>

        } else {

            echo "[" . date("Y-m-d H:i:s") . "] last health report is missing, ignored.\n";

        }

        return $htmlBody;
    }

    /**
     * @throws \Exception
     */
    function processTopicQueue()
    {
    }

}

