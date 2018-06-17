<?php
/**
 * Queue processor to clear up the queue and send email.
 *
 */

require('vendor/autoload.php');

echo "[" . date("Y-m-d H:i:s") . "] starting queue processing.\n";

$localQueueDirName = "/mqtt-topics-queue";
$pathToCapturedImages = "/etc/opt/kerberosio/capture";

//process the queue
$dirHandle = opendir($localQueueDirName);
if (!$dirHandle) {
    throw new \Exception("Cannot open directory $localQueueDirName");
}

while (($fileName = readdir($dirHandle)) !== false) {

    if (!preg_match('/.*\.json$/i', $fileName)) {
        continue;
    }

    echo "[" . date("Y-m-d H:i:s") . "] processing $fileName \n";

    $queueItemData = file_get_contents($localQueueDirName . "/" . $fileName);
    if (empty($queueItemData)) {
        throw new \Exception("Cannot get content of file " . $localQueueDirName . "/" . $fileName);
    }
    echo "[" . date("Y-m-d H:i:s") . "] content =  " . $queueItemData . "\n";
    $queueItemData = json_decode($queueItemData);
    //print_r($queueItemData);
    $pathToImage = $pathToCapturedImages . "/" . $queueItemData->payload->pathToImage;
    //var_dump($pathToImage);
    //exit;

    break;
    //unlink($fileName);
};

echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";