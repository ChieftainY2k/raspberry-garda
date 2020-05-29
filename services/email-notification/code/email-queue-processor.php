<?php
/**
 * Queue processor to send all emails pending in the email queue
 *
 * This script takes each message from the queue and sends it via SMTP server
 *
 * @TODO this is just MVP/PoC, refactor it , use DI!
 */

use PHPMailer\PHPMailer\PHPMailer;

try {

    //init
    echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] starting email queue processing.\n";
    require(__DIR__."/bootstrap.php");

    //@TODO use DI/Config here

    //mqtt client
    $mqttClientId = basename(__FILE__)."-".uniqid("");
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
    $emailQueuePath = "/mydata/email-queues";

    //queue processor
    $queueProcessor = new \EmailNotifier\EmailQueueProcessor($mailer, $mqttClient, $emailQueuePath);
    $queueProcessor->processEmailQueue();

    //clean up
    $mqttClient->disconnect();

    echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] finished email queue processing.\n";

} catch (Exception $e) {

    echo "[".date("Y-m-d H:i:s")."][".basename(__FILE__)."] EXCEPTION: ".$e.".\n\nSleeping for a while...\n";
    sleep(60 * 10);

}

