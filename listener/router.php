<?php

//output it
$msg = "[" . date("Y-m-d H:i:s") . "] Listener: GET = " . json_encode($_GET) . " \n";
echo $msg;

//log it
file_put_contents("router.log", $msg, FILE_APPEND);
