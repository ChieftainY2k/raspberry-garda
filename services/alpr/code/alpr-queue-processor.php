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


echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";


