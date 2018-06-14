<?php

$rawData = file_get_contents("php://input");

//output it
$msg = "[" . date("Y-m-d H:i:s") . "] Listener: REQUEST = " . json_encode($_REQUEST) . ", RAW POST = " . $rawData . "";
echo $msg . "\n";

//log to stderr
error_log($msg);

////log to file
//file_put_contents("listener.log", $msg, FILE_APPEND);

