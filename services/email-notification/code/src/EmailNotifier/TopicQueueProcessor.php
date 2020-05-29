<?php

namespace EmailNotifier;

use Mosquitto\Client;
use PHPMailer\PHPMailer\PHPMailer;

/**
 *
 * @TODO use logger object
 * @TODO use objects/factories for better testing
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
     * @var string
     */
    private $pathToCapturedImages;

    /**
     *
     * @param string $topicQueueRootPath
     * @param string $emailQueueRootPath
     * @param string $lastHealthReportFile
     * @param string $pathToCapturedImages
     * @throws \Exception
     */
    function __construct(string $topicQueueRootPath, string $emailQueueRootPath, string $lastHealthReportFile, string $pathToCapturedImages)
    {
        $this->topicQueueRootPath = $topicQueueRootPath;
        $this->emailQueueRootPath = $emailQueueRootPath;
        $this->lastHealthReportFile = $lastHealthReportFile;
        $this->pathToCapturedImages = $pathToCapturedImages;

        //@FIXME use DI/Config here

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
        echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] ".$msg."\n";
    }

    ///**
    // * @return null|string
    // */
    //function getLastHealthReportAsHtml()
    //{
    //    $htmlBody = null;
    //
    //    //attach last health report if available
    //    if (file_exists($this->lastHealthReportFile)) {
    //
    //        $lastHealthReportData = file_get_contents($this->lastHealthReportFile);
    //        $lastHealthReportData = json_decode($lastHealthReportData, true);
    //        $reportPayload = $lastHealthReportData['payload'];
    //        $uptimeSeconds = $reportPayload['uptime_seconds'];
    //        $htmlBody .= "
    //            <ul>
    //                <li>Report time: <b>".date("Y-m-d H:i:s", $reportPayload['timestamp'])."</b></li>
    //                <li>System name: <b>".$reportPayload['system_name']."</b></li>
    //                <li>Uptime: <b>".floor($uptimeSeconds / 3600)."h ".gmdate("i", $uptimeSeconds % 3600)."m</b></li>
    //                <li>CPU: <b>".$reportPayload['cpu_temp']."'C</b> , <b>".$reportPayload['cpu_voltage']."V</b></li>
    //                <li>Disk:
    //                    <b>".number_format($reportPayload['disk_space_available_kb'] / 1024 / 2014, 2)." GB
    //                    (".number_format(100 * ($reportPayload['disk_space_available_kb'] / $reportPayload['disk_space_total_kb']), 2)."%) </b> available ,
    //                    <b>".number_format($reportPayload['disk_space_total_kb'] / 1024 / 1024, 2)." GB </b>
    //                    total
    //
    //                </li>
    //            </ul>
    //        ";
    //        //<li>Uptime: <b>" . gmdate("H:i:s", $reportPayload['uptime_seconds']) . " (hours:minutes:seconds)</b></li>
    //
    //    } else {
    //
    //        $this->log("last health report is missing, ignored.");
    //
    //    }
    //
    //    return $htmlBody;
    //}

    /**
     * @throws \Exception
     */
    function processTopicQueueForKerberosMotionDetection()
    {

        $queueProcessedItemsList = [];
        $fileListToAttach = [];

        //scan directory for queued topics data, sort it by name, ascending
        $files = scandir($this->topicQueueRootPath, SCANDIR_SORT_ASCENDING);
        if ($files === false) {
            throw new \Exception("Cannot open directory ".$this->topicQueueRootPath."");
        }

        //scan all files in queue directory
        foreach ($files as $queueItemFileName) {

            if (!preg_match('/.*\.json$/i', $queueItemFileName)) {
                continue;
            }

            $this->log("processing $queueItemFileName");

            $queueItemData = file_get_contents($this->topicQueueRootPath."/".$queueItemFileName);
            if (empty($queueItemData)) {
                throw new \Exception("Cannot get content of file ".$this->topicQueueRootPath."/".$queueItemFileName);
            }
            $this->log("content =  ".$queueItemData."");
            $queueItemData = json_decode($queueItemData, true);

            //{
            //    "timestamp": 1590761879,
            //    "topic": "kerberos\/motiondetected",
            //    "payload": {
            //        "regionCoordinates": [3, 3, 1279, 959],
            //        "numberOfChanges": 339258,
            //        "pathToVideo": "1590761878_6-725545_kerberos_3-3-1279-959_339258_691.mp4",
            //        "name": "kerberos",
            //        "timestamp": "1590761878",
            //        "microseconds": "6-725691",
            //        "token": 691,
            //        "pathToImage": "1590761878_6-725691_kerberos_3-3-1279-959_339258_691.jpg"
            //    }
            //}

            //@TODO add topic validation
            //@TODO add data validation here
            //@TODO check if media file still exists
            if (!empty($queueItemData['payload']['pathToImage'])) {

                $imageFileName = $queueItemData['payload']['pathToImage'];
                $imageFullPath = $this->pathToCapturedImages."/".$imageFileName;

                //@TODO resize images to cut the email size
                //@TODO do not include images that are created shortly one after other

                //register an attachment for inclusion
                $fileListToAttach[] = ["filePath" => $imageFullPath];

            }
            //remember that this queue item was processed
            $queueProcessedItemsList[] = $queueItemFileName;

            //do not process too many items at once
            if (count($queueProcessedItemsList) >= 25) {
                break;
            }

            //break;
        };


        //do we need to send an email ?
        if (empty($queueProcessedItemsList)) {
            //no files processed
            return;
        }

        //email content
        $emailSubject = ''.getenv("KD_SYSTEM_NAME").' - motion detected.';
        $emailHtmlBody = "
            Motion detected on <b>".getenv("KD_SYSTEM_NAME")."</b> at local time <b>".date("Y-m-d H:i:s")."</b><br>
        ";

        ////attach health report if available
        //$lastHealthReportAsHtml = $this->getLastHealthReportAsHtml();
        //if (!empty($lastHealthReportAsHtml)) {
        //    $emailHtmlBody .= "Last health report: <br>".$lastHealthReportAsHtml;
        //}

        //create email data
        $recipient = getenv("KD_EMAIL_NOTIFICATION_RECIPIENT");
        //@TODO use DTO here
        $emailData = [
            "recipients" => [
                $recipient,
            ],
            "subject" => $emailSubject,
            "htmlBody" => $emailHtmlBody,
            "attachments" => $fileListToAttach,
        ];

        //print_r($emailData);
        //exit;

        //save email data to temporary JSON file
        $filePath = $this->emailQueueRootPath."/".(microtime(true)).".json";
        $filePathTmp = $filePath.".tmp";
        if (!file_put_contents($filePathTmp, json_encode($emailData), LOCK_EX)) {
            throw new \Exception("Cannot save data to file ".$filePath);
        }

        //rename temporaty file to dest file
        if (!rename($filePathTmp, $filePath)) {
            throw new \Exception("Cannot rename file $filePathTmp to $filePath");
        }

        //remove the processed topic items
        if (!empty($queueProcessedItemsList)) {

            $this->log("removing processed ".count($queueProcessedItemsList)." item(s) from queue.");

            foreach ($queueProcessedItemsList as $queueItemFileName) {
                //remote the file
                if (!unlink($this->topicQueueRootPath."/".$queueItemFileName)) {
                    throw new \Exception("Cannot remove file ".$this->topicQueueRootPath."/".$queueItemFileName."");
                }
            }
        }

        $this->log("finished queue processing.");


    }

}

