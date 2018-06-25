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

echo "[" . date("Y-m-d H:i:s") . "] starting the processor.\n";

//load the services configuration
(new Dotenv\Dotenv("/service-configs","services.conf"))->load();


if (intval(getenv("KD_ALPR_ENABLED")) != 1) {
    echo "[" . date("Y-m-d H:i:s") . "] WARNING: ALPR service is DISABLED, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

if (
    empty(getenv("KD_ALPR_ENABLED"))
    or empty(getenv("KD_ALPR_COUNTRY"))
) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: some of the required environment params are empty, sleeping and exiting.\n";
    sleep(60 * 15);
    exit;
}

echo "[" . date("Y-m-d H:i:s") . "] starting queue processing.\n";

//$command = "alpr -c eu /etc/opt/kerberosio/capture/1529534045_6-287667_kerberosInDocker_23-38-789-632_15459_41.jpg"; //@TODO remove after tests
//$output = shell_exec($command);
//preg_match_all("/\-\\s+([0-9a-z]+)\\s+confidence:\\s+([0-9.]+)/im", $output, $matches);
//$foundNumbersList = [];
//if (!empty($matches)) {
//
//    for ($i = 0; isset($matches[0][$i]); $i++) {
//        $foundNumbersList[] = [
//            "number" => $matches[1][$i],
//            "confidence" => $matches[2][$i]
//        ];
//    }
//
//    //publish a topic that an email has been just sent
//    $clientId = basename(__FILE__) . "-" . uniqid("");
//    $client = new Mosquitto\Client($clientId);
//    $client->connect("mqtt-server", 1883, 60);
//    $client->publish("alpr/detection", json_encode([
//        "system_name" => getenv("KD_SYSTEM_NAME"),
//        "timestamp" => time(),
//        "alpr_result" => $foundNumbersList
//    ]), 1, false);
//    $client->disconnect();
//
//}
////print_r($foundNumbersList);
//exit;


$localQueueDirName = "/data/topics-queue";
$recognizedPlatesDatabaseDirName = "/data/recognized-numbers";
$pathToCapturedImages = "/etc/opt/kerberosio/capture";
//process the queue
$dirHandle = opendir($localQueueDirName);
if (!$dirHandle) {
    throw new \Exception("Cannot open directory $localQueueDirName");
}

//scan all files in queue directory
while (($queueItemFileName = readdir($dirHandle)) !== false) {

    if (!preg_match('/.*\.json$/i', $queueItemFileName)) {
        continue;
    }

    echo "[" . date("Y-m-d H:i:s") . "] processing $queueItemFileName \n";

    $queueItemData = file_get_contents($localQueueDirName . "/" . $queueItemFileName);
    if (empty($queueItemData)) {
        throw new \Exception("Cannot get content of file " . $localQueueDirName . "/" . $queueItemFileName);
    }
    echo "[" . date("Y-m-d H:i:s") . "] content =  " . $queueItemData . "\n";
    $queueItemData = json_decode($queueItemData);

    $imageFileName = $queueItemData->payload->pathToImage;
    if (!empty($imageFileName)) {

        $imageFullPath = $pathToCapturedImages . "/" . $imageFileName;

        $command = "alpr -c " . getenv("KD_ALPR_COUNTRY") . " " . $imageFullPath;
        //$command = "alpr -c eu /etc/opt/kerberosio/capture/1529534045_6-287667_kerberosInDocker_23-38-789-632_15459_41.jpg"; //@TODO remove after tests
        echo "[" . date("Y-m-d H:i:s") . "] executing: " . $command . "\n";
        $output = shell_exec($command);
        //echo "[" . date("Y-m-d H:i:s") . "] output: " . $output . "";

        //break down the alpr output into numbers and confidence
        preg_match_all("/\-\\s+([0-9a-z]+)\\s+confidence:\\s+([0-9.]+)/im", $output, $matches);
        if (!empty($matches[0])) {

            //echo "[" . date("Y-m-d H:i:s") . "] ALPR found " . count($matches[0]) . " possible numbers.\n";
            $foundNumbersList = [];

            for ($i = 0; isset($matches[0][$i]); $i++) {
                $foundNumbersList[] = [
                    "number" => $matches[1][$i],
                    "confidence" => $matches[2][$i]
                ];
            }

            echo "[" . date("Y-m-d H:i:s") . "] numbers found: " . json_encode($foundNumbersList) . "\n";

            //save to local db for later
            $filePath = $recognizedPlatesDatabaseDirName . "/" . (microtime(true)) . "-numbers.json";
            if (!file_put_contents($filePath, json_encode([
                "timestamp" => time(),
                "image" => $imageFullPath,
                "numbers" => $foundNumbersList,
            ]), LOCK_EX)) {
                throw new \Exception("Cannot save data to file " . $filePath);
            }
            echo "[" . date("Y-m-d H:i:s") . "] saved numbers to db file $filePath\n";


            //publish topic that plate numbers were found
            $clientId = basename(__FILE__) . "-" . uniqid("");
            $client = new Mosquitto\Client($clientId);
            $client->connect("mqtt-server", 1883, 60);
            $client->publish("alpr/detection", json_encode([
                "system_name" => getenv("KD_SYSTEM_NAME"),
                "timestamp" => time(),
                "alpr_result" => $foundNumbersList
            ]), 1, false);
            $client->disconnect();

        } else {

            echo "[" . date("Y-m-d H:i:s") . "] NO numbers found in the image.\n";

        }

    }

    //remove the file from queue
    if (!unlink($localQueueDirName . "/" . $queueItemFileName)) {
        throw new \Exception("Cannot remove file " . $localQueueDirName . "/" . $queueItemFileName . "");
    }
    echo "[" . date("Y-m-d H:i:s") . "] topic data successfully removed from the queue.\n";

}


echo "[" . date("Y-m-d H:i:s") . "] finished queue processing.\n";
