<?php

namespace EmailNotifier;

use Mosquitto\Client;
use PHPMailer\PHPMailer\PHPMailer;

/**
 *
 * @TODO use logger object
 */
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
     * @throws \Exception
     */
    function __construct(PHPMailer $mailer, Client $mqttClient, string $queueRootPath)
    {
        $this->mqttClient = $mqttClient;
        $this->mailer = $mailer;
        $this->queueRootPath = $queueRootPath;

        //@FIXME use DI/Config here

        if (empty(getenv("KD_SYSTEM_NAME"))) {
            throw new \Exception("Empty environment variable KD_SYSTEM_NAME");
        }
        if (empty(getenv("KD_REMOTE_SMTP_FROM"))) {
            throw new \Exception("Empty environment variable KD_REMOTE_SMTP_FROM");
        }

    }

    /**
     * @param $msg
     */
    function log($msg)
    {
        echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] ".$msg."\n";
    }

    /**
     * @param string $subQueuePath
     * @throws \Exception
     */
    function processEmailQueue($subQueuePath = "")
    {

        //process the queue
        $dirPath = $this->queueRootPath."/".$subQueuePath;

        $this->log("processing directory $dirPath ...");

        //@TODO sort files by filename ascending

        $dirHandle = opendir($dirPath);
        if (!$dirHandle) {
            throw new \Exception("Cannot open directory ".$dirPath."");
        }

        //scan all files in queue directory
        while (($fileName = readdir($dirHandle)) !== false) {

            //echo $fileName . "\n";

            if (!preg_match("/^[a-z0-9_-]+(\.[a-z0-9_.-]+)?$/i", $fileName)) {
                continue;
            }

            //process a sub-directory
            if (is_dir($dirPath."/".$fileName)) {
                $this->processEmailQueue($fileName);
                continue;
            }

            //process a file, ignore non-json extensions
            if (!preg_match('/\.json$/i', $fileName)) {
                continue;
            }

            $this->processFile($dirPath."/".$fileName);

            //remove processed queue item
            if (!unlink($dirPath."/".$fileName)) {
                throw new \Exception("Cannot remove file ".$dirPath."/".$fileName);
            }
            $this->log("successfully removed ".$dirPath."/".$fileName." from queue.");
        };

        //$this->log("finished processing directory $dirPath ...");

    }

    /**
     * @param $filePath
     * @throws \Exception
     */
    function processFile($filePath)
    {
        $this->log("processing file $filePath");

        //unserializejson data
        $itemDataJson = file_get_contents($filePath);

        //$this->log("json data = " . $itemDataJson . ""); exit;

        //@TODO validate loaded data before processing

        //@TODO use DTO here
        $itemData = json_decode($itemDataJson, true);

        if (empty($itemData)) {
            throw new \Exception("Invalid json in $filePath");
        }

        $mailer = $this->mailer;

        //clear all settings
        $mailer->clearAddresses();
        $mailer->clearCCs();
        $mailer->clearBCCs();
        $mailer->clearAllRecipients();
        $mailer->clearAttachments();
        $mailer->clearReplyTos();
        $mailer->clearCustomHeaders();

        //recipients
        $mailer->setFrom(getenv("KD_REMOTE_SMTP_FROM"));
        foreach ($itemData['recipients'] as $recipient) {
            $mailer->addAddress($recipient);
        }

        //Add requested attachments
        if (empty($itemData['attachments'])) {
            $itemData['attachments'] = [];
        }
        foreach ($itemData['attachments'] as $attachmentData) {
            if (file_exists($attachmentData['filePath'])) {
                $mailer->addAttachment($attachmentData['filePath']);
            } else {
                $this->log("warning: skipping missing attachment file ".$attachmentData['filePath']);
            }
        }


        //Content
        $mailer->isHTML(true);                                  // Set email format to HTML
        $mailer->Subject = $itemData['subject'];
        $mailer->Body = $itemData['htmlBody'];
        $mailer->addCustomHeader("x-local-time", date("Y-m-d H:i:s"));
        $result = $mailer->send();
        if (!$result) {
            throw new \Exception("Cannot send email: ".$mailer->ErrorInfo);
        }

        $this->log("successfully sent email to ".json_encode($itemData['recipients'])." with subject ".json_encode($itemData['subject']).", with ".count($itemData['attachments'])." attachments");

        $this->mqttClient->publish(
            "notification/email/sent",
            json_encode(
                [
                    "system_name" => getenv("KD_SYSTEM_NAME"),
                    "timestamp" => time(),
                    "local_time" => date("Y-m-d H:i:s"),
                    //"recipient" => getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"),
                    "recipients" => $itemData['recipients'],
                    "subject" => $mailer->Subject,
                    "service" => basename(__FILE__),
                    "attachment_count" => count($itemData['attachments']),
                ]
            ),
            1,
            false
        );

    }

}

///*
//Array
//(
//    [subject] => email subject
//    [htmlBody] => <b>html body</b>
//    [recipients] => Array
//        (
//            [0] => ChieftainY2k@gmail.com
//        )
//
//    [attachments] => Array
//        (
//            [0] => Array
//                (
//                    [filePath] => /etc/opt/kerberosio/capture/1530454754_6-367298_kerberosInDocker_49-244-789-629_42588_373.jpg
//                )
//
//        )
//
//)
//*/