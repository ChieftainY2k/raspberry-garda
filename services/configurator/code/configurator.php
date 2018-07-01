<?php
/**
 * Simple UI to update config and reload docker containers.
 *
 * @FIXME This is just a proof of concept, refactor it!
 */

use Configurator\Configurator;

require(__DIR__ . "/vendor/autoload.php");

//load services config
(new Dotenv\Dotenv("/service-configs", "services.conf"))->load();

try {

    //connect to the mqtt server, listen for topics
    $clientId = basename(__FILE__) . "-" . uniqid("");
    $client = new Mosquitto\Client($clientId);
    $client->connect("mqtt-server", 1883, 60);

    //$client->publish("configurator/config/updated", json_encode([
    //    "system_name" => getenv("KD_SYSTEM_NAME"),
    //    "timestamp" => time(),
    //    "local_time" => date("Y-m-d H:i:s"),
    //]), 1, false);
    //exit;

    //create configurator
    $configurator = new Configurator($client);
    $configurator->showUI();

    $client->disconnect();

} catch (\Exception $e) {

    echo "
        <div style='padding:10px; background:red; color:white;'>
            Exception: <br><br.
            <b>" . htmlspecialchars($e->getMessage()) . "</b><br><br>
            Stack trace:<br>
            <pre>" . $e . "</pre>
        </div>";
}


