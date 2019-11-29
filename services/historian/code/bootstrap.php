<?php

/**
 * Common bootstrapper
 */

//configure 3rt party libraries
require('vendor/autoload.php');
//load the services configuration
(new Dotenv\Dotenv("/service-configs", "services.conf"))->load();

if (empty(getenv("KD_SYSTEM_NAME"))) {
    throw new \Exception("Empty environment variable KD_SYSTEM_NAME");
}

