<?php
/**
 * Queue processor to clear up the queue and send aggregated email.
 *
 * This cript takes all messages from the queue (collected by the mqtt events collector) since
 * the last run, aggregates the content and sends email to designated recipients.
 *
 * @TODO this is just MVP/PoC, refactor it !
 */

use PHPMailer\PHPMailer\PHPMailer;

require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting queue processing.\n";

//load the services configuration
(new Dotenv\Dotenv("/service-configs","services.conf"))->load();


if (intval(getenv("KD_EMAIL_NOTIFICATION_ENABLED")) != 1) {
    echo "[" . date("Y-m-d H:i:s") . "] WARNING: Email notification service is DISABLED, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

//check environment params
if (
    empty(getenv("KD_EMAIL_NOTIFICATION_ENABLED"))
    or empty(getenv("KD_REMOTE_SMTP_HOST"))
    or empty(getenv("KD_REMOTE_SMTP_USERNAME"))
    or empty(getenv("KD_REMOTE_SMTP_PASSWORD"))
    or empty(getenv("KD_REMOTE_SMTP_SECURE_METHOD"))
    or empty(getenv("KD_REMOTE_SMTP_PORT"))
    or empty(getenv("KD_REMOTE_SMTP_FROM"))
    or empty(getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"))
    or empty(getenv("KD_SYSTEM_NAME"))
) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

//@TODO make it shared
$lastHealthReportFile = "/tmp/health-report.json";
$localQueueDirName = "/data/topics-queue";
$pathToCapturedImages = "/etc/opt/kerberosio/capture";

$queueProcessedItemsList = [];

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
    //@TODO check if media file still exists
    if (!empty($queueItemData->payload->pathToImage)) {

        $imageFileName = $queueItemData->payload->pathToImage;
        $imageFullPath = $pathToCapturedImages . "/" . $imageFileName;

        //$htmlBody .= "<li>" . $imageFileName . "";

        //@TODO resize images to cut the email size
        //@TODO do not include images that are created shortly one after other

        //register an attachment for inclusion
        $fileListToAttach[] = $imageFullPath;

    }
    //remember that this queue item was processed
    $queueProcessedItemsList[] = $queueItemFileName;

    //do not process too many items at once
    if (count($queueProcessedItemsList) >= 25) {
        break;
    }
};


if (!empty($fileListToAttach)) {

    echo "[" . date("Y-m-d H:i:s") . "] sending alert email to recipients.\n";

    $htmlBody .= "Motion detected on <b>" . getenv("KD_SYSTEM_NAME") . "</b>. See the attached media for details.";

    //attach last health report if available
    if (file_exists($lastHealthReportFile)) {

        $lastHealthReportData = file_get_contents($lastHealthReportFile);
        //$htmlBody .= "<br><br>Last health report: <br><pre>" . var_export(json_decode($lastHealthReportData, true), true) . "</pre>";
        $lastHealthReportData = json_decode($lastHealthReportData, true);
        $reportPayload = $lastHealthReportData['payload'];
        $htmlBody .= "
        <br><br>Last health report (reported " . date("Y-m-d H:i:s", $reportPayload['timestamp']) . "): <br>
        <ul>
            <li>System name: <b>" . $reportPayload['system_name'] . "</b></li>
            <li>Uptime: <b>" . $reportPayload['uptime_seconds'] . " sec.</b></li>
            <li>CPU temp.: <b>" . $reportPayload['cpu_temp'] . " 'C</b></li>
            <li>CPU volt.: <b>" . $reportPayload['cpu_voltage'] . " V</b></li>
            <li>Disk space total: <b>" . $reportPayload['disk_space_total_kb'] . " kb</b></li>
            <li>Disk space available: <b>" . $reportPayload['disk_space_available_kb'] . " kb 
                (" . number_format(100 * ($reportPayload['disk_space_available_kb'] / $reportPayload['disk_space_total_kb']), 2) . "%)</b>
            </li>
        </ul>";

    } else {

        echo "[" . date("Y-m-d H:i:s") . "] last health report is missing, ignored.\n";

    }

    //@TODO if we cannot send an email thel sleep for a while until the SMTP server is back
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
        if (file_exists($attachmentFilePath)) {
            $mail->addAttachment($attachmentFilePath);
        }
    }

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = '[' . getenv("KD_SYSTEM_NAME") . '] motion detected.';
    $mail->Body = $htmlBody;
    $result = $mail->send();
    if (!$result) {
        //throw new \Exception("Cannot send email: " . $mail->ErrorInfo);
        echo "[" . date("Y-m-d H:i:s") . "] ERROR: cannot send email, SMTP problems. Sleeping for a while and exitng...\n";
        sleep(60 * 10);
        exit;
    }
    //echo "[" . date("Y-m-d H:i:s") . "] email sent.\n";

    //publish a topic that an email has been just sent
    $clientId = basename(__FILE__) . "-" . uniqid("");
    $client = new Mosquitto\Client($clientId);
    $client->connect("mqtt-server", 1883, 60);
    $client->publish("notification/email/sent", json_encode([
        "system_name" => getenv("KD_SYSTEM_NAME"),
        "timestamp" => time(),
        "recipient" => getenv("KD_EMAIL_NOTIFICATION_RECIPIENT"),
        "subject" => $mail->Subject,
        "service" => basename(__FILE__),
        "attachmentCount" => count($fileListToAttach),
    ]), 1, false);
    $client->disconnect();


}

//remove the processed queue items
if (!empty($queueProcessedItemsList)) {

    echo "[" . date("Y-m-d H:i:s") . "] removing processed " . count($queueProcessedItemsList) . " item(s) from queue.\n";

    foreach ($queueProcessedItemsList as $queueItemFileName) {
        //remote the file
        if (!unlink($localQueueDirName . "/" . $queueItemFileName)) {
            throw new \Exception("Cannot remove file " . $localQueueDirName . "/" . $queueItemFileName . "");
        }
    }
}

echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";


