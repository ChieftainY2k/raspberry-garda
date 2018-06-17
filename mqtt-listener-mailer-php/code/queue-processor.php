<?php
/**
 * Queue processor to clear up the queue and send email.
 *
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting queue processing.\n";

$localQueueDirName = "/mqtt-topics-queue";
$pathToCapturedImages = "/etc/opt/kerberosio/capture";

//process the queue
$dirHandle = opendir($localQueueDirName);
if (!$dirHandle) {
    throw new \Exception("Cannot open directory $localQueueDirName");
}

while (($fileName = readdir($dirHandle)) !== false) {

    if (!preg_match('/.*\.json$/i', $fileName)) {
        continue;
    }

    echo "[" . date("Y-m-d H:i:s") . "] processing $fileName \n";

    $queueItemData = file_get_contents($localQueueDirName . "/" . $fileName);
    if (empty($queueItemData)) {
        throw new \Exception("Cannot get content of file " . $localQueueDirName . "/" . $fileName);
    }
    echo "[" . date("Y-m-d H:i:s") . "] content =  " . $queueItemData . "\n";
    $queueItemData = json_decode($queueItemData);
    //print_r($queueItemData);
    $pathToImage = $pathToCapturedImages . "/" . $queueItemData->payload->pathToImage;
    //var_dump($pathToImage);
    //exit;


    break;
    //unlink($fileName);
};


//Server settings (see docker env params for details)
$mail = new PHPMailer(true);
$mail->SMTPDebug = 2;                                 // Enable verbose debug output
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = getenv("REMOTE_SMTP_HOST");  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = getenv("REMOTE_SMTP_USERNAME");                 // SMTP username
$mail->Password = getenv("REMOTE_SMTP_PASSWORD");                           // SMTP password
$mail->SMTPSecure = getenv("REMOTE_SMTP_SECURE_METHOD");                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = getenv("REMOTE_SMTP_PORT");                                    // TCP port to connect to

////Recipients
//$mail->setFrom('from@example.com', 'Mailer');
//$mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
//$mail->addAddress('ellen@example.com');               // Name is optional
//$mail->addReplyTo('info@example.com', 'Information');
//$mail->addCC('cc@example.com');
//$mail->addBCC('bcc@example.com');
//
////Attachments
//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
//
////Content
//$mail->isHTML(true);                                  // Set email format to HTML
//$mail->Subject = 'Here is the subject';
//$mail->Body = 'This is the HTML message body <b>in bold!</b>';
//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
//
//$mail->send();


echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";