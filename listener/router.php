<?php

$rawData = file_get_contents("php://input");

//output it
$msg = "[" . date("Y-m-d H:i:s") . "] Listener: REQUEST = " . json_encode($_REQUEST) . ", RAW POST = " . $rawData . "\n";
echo $msg;

//log it
file_put_contents("router.log", $msg, FILE_APPEND);
