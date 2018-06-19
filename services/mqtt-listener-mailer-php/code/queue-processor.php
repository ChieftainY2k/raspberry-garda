<?php
/**
 * Queue processor to clear up the queue and send aggregated email.
 *
 * This cript takes all messages from the queue (collected by the mqtt events collector) since
 * the last run, aggregates the content and sends email to designated recipients.
 *
 *
 */

//@TODO this is just MVP/PoC, refactor it !

use PHPMailer\PHPMailer\PHPMailer;

require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting queue processing.\n";

//check environment params
if (
    empty(getenv("KD_REMOTE_SMTP_HOST"))
    or empty(getenv("KD_REMOTE_SMTP_USERNAME"))
    or empty(getenv("KD_REMOTE_SMTP_PASSWORD"))
    or empty(getenv("KD_REMOTE_SMTP_SECURE_METHOD"))
    or empty(getenv("KD_REMOTE_SMTP_PORT"))
    or empty(getenv("KD_REMOTE_SMTP_FROM"))
    or empty(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"))
    or empty(getenv("KD_SYSTEM_NAME"))
) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(3600);
    exit;
}

//@TODO make it shared
$lastHealthReportFile = "/tmp/health-report.json";
$localQueueDirName = "/mqtt-topics-queue";
$pathToCapturedImages = "/etc/opt/kerberosio/capture";

$queueProcessedFilesList = [];

$htmlBody = "";
$fileListToAttach = [];

//process the queue
$dirHandle = opendir($localQueueDirName);
if (!$dirHandle) {
    throw new \Exception("Cannot open directory $localQueueDirName");
}

//scan all files in queue directory
while (($queueItemFileName = readdir($dirHandle)) !== false) {

    if (!preg_match('/.*\.json$/i', $queueItemFileName)) {
        continue;
    }

    echo "[" . date("Y-m-d H:i:s") . "] processing $queueItemFileName \n";

    $queueItemData = file_get_contents($localQueueDirName . "/" . $queueItemFileName);
    if (empty($queueItemData)) {
        throw new \Exception("Cannot get content of file " . $localQueueDirName . "/" . $queueItemFileName);
    }
    echo "[" . date("Y-m-d H:i:s") . "] content =  " . $queueItemData . "\n";
    $queueItemData = json_decode($queueItemData);

    //{"timestamp":1529315427,"topic":"kerberos\/machinery\/detection\/motion","payload":{"regionCoordinates":[23,273,789,631],"numberOfChanges":17393,"pathToVideo":"1529315426_6-874214_kerberosInDocker_23-273-789-631_17393_386.mp4","name":"kerberosInDocker","timestamp":"1529315426","microseconds":"6-874367","token":386,"pathToImage":"1529315426_6-874367_kerberosInDocker_23-273-789-631_17393_386.jpg"}}

    //@TODO add data validation here
    $imageFileName = $queueItemData->payload->pathToImage;
    if (!empty($imageFileName)) {

        $imageFullPath = $pathToCapturedImages . "/" . $imageFileName;

        $htmlBody .= "<li>" . $imageFileName . "";

        //@TODO resize images to cut the email size
        //@TODO do not include images that are created shortly one after other

        //register an attachment for inclusion
        $fileListToAttach[] = $imageFullPath;

    }
    //remember that this queue item was processed
    $queueProcessedFilesList[] = $queueItemFileName;

    //do not process too many items at once
    if (count($queueProcessedFilesList) >= 20) {
        break;
    }
};


if (!empty($htmlBody)) {

    echo "[" . date("Y-m-d H:i:s") . "] sending alert email to recipients.\n";

    //attach last health report if available
    if (file_exists($lastHealthReportFile)) {
        $lastHealthReportData = file_get_contents($lastHealthReportFile);
        $htmlBody .= "<hr>Last health report:<br>" . $lastHealthReportData;
    }

    //Server settings (see docker env params for details)
    $mail = new PHPMailer(true);
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = getenv("KD_REMOTE_SMTP_HOST");  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = getenv("KD_REMOTE_SMTP_USERNAME");                 // SMTP username
    $mail->Password = getenv("KD_REMOTE_SMTP_PASSWORD");                           // SMTP password
    $mail->SMTPSecure = getenv("KD_REMOTE_SMTP_SECURE_METHOD");                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = getenv("KD_REMOTE_SMTP_PORT");                                    // TCP port to connect to

    //Recipients
    $mail->setFrom(getenv("KD_REMOTE_SMTP_FROM"));
    $mail->addAddress(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"));

    //Add requested attachments
    foreach ($fileListToAttach as $attachmentFilePath) {
        $mail->addAttachment($attachmentFilePath);
    }

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = '[' . getenv("KD_SYSTEM_NAME") . '] motion detected.';
    $mail->Body = $htmlBody;
    $result = $mail->send();
    if (!$result) {
        throw new \Exception("Cannot send email: " . $mail->ErrorInfo);
    }
    //echo "[" . date("Y-m-d H:i:s") . "] email sent.\n";
}

//remove the processed queue items
if (!empty($queueProcessedFilesList)) {

    echo "[" . date("Y-m-d H:i:s") . "] removing processed " . count($queueProcessedFilesList) . " item(s) from queue.\n";

    foreach ($queueProcessedFilesList as $queueItemFileName) {
        //remote the file
        if (!unlink($localQueueDirName . "/" . $queueItemFileName)) {
            throw new \Exception("Cannot remove file " . $localQueueDirName . "/" . $queueItemFileName . "");
        }
    }
}

echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";

