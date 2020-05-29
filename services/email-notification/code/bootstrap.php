<?php

/**
 * Common bootstrapper
 */

//configure 3rt party libraries
require('vendor/autoload.php');
//load the services configuration
(new Dotenv\Dotenv("/service-configs", "services.conf"))->load();

//check environment params
if (intval(getenv("KD_EMAIL_NOTIFICATION_ENABLED")) != 1) {
    echo "[" . date("Y-m-d H:i:s") . "][" . basename(__FILE__) . "] WARNING: Email notification service is DISABLED, sleeping and exiting.\n";
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
    echo "[" . date("Y-m-d H:i:s") . "][" . basename(__FILE__) . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

