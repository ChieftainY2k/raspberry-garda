<?php
/**
 * Queue processor to clear up the queue and detect car license plate numer
 *
 * This cript takes all messages from the queue (collected by the mqtt events collector) since
 * the last run and processes them to find car license plate numbers
 *
 *
 */

//@TODO this is just MVP/PoC, refactor it !

require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting queue processing.\n";


echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";


