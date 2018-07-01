<?php
/**
 * Queue processor to send all emails pending in the email queue
 *
 * This script takes each message from the queue and sends it via SMTP server
 *
 * @TODO this is just MVP/PoC, refactor it !
 */

use PHPMailer\PHPMailer\PHPMailer;

//init
echo "[" . date("Y-m-d H:i:s") . "] starting email queue processing.\n";
require(__DIR__ . "/bootstrap.php");

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

//mqtt client
$mqttClientId = basename(__FILE__) . "-" . uniqid("");
$mqttClient = new Mosquitto\Client($mqttClientId);
$mqttClient->connect("mqtt-server", 1883, 60);

//SMTP client
$mailer = new PHPMailer(true);
//$mail->SMTPDebug = 2;                                 // Enable verbose debug output
$mailer->isSMTP();                                      // Set mailer to use SMTP
$mailer->Host = getenv("KD_REMOTE_SMTP_HOST");  // Specify main and backup SMTP servers
$mailer->SMTPAuth = true;                               // Enable SMTP authentication
$mailer->Username = getenv("KD_REMOTE_SMTP_USERNAME");                 // SMTP username
$mailer->Password = getenv("KD_REMOTE_SMTP_PASSWORD");                           // SMTP password
$mailer->SMTPSecure = getenv("KD_REMOTE_SMTP_SECURE_METHOD");                            // Enable TLS encryption, `ssl` also accepted
$mailer->Port = getenv("KD_REMOTE_SMTP_PORT");                                    // TCP port to connect to

//queue root path
$localQueueDirName = "/data/email-queues";

//queue processor
$queueProcessor = new \EmailNotifier\EmailQueueProcessor($mailer, $mqttClient, $localQueueDirName);
$queueProcessor->processQueue();

//clean up
$mqttClient->disconnect();

echo "[" . date("Y-m-d H:i:s") . "] finished email queue processing.\n";