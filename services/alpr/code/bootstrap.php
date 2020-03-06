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
    echo "[" . date("Y-m-d H:i:s") . "] WARNING: Email notification service is DISABLED, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}
