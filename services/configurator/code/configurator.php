<?php
/**
 * Simple UI to update config and reload docker containers.
 *
 * @FIXME This is just a proof of concept, refactor it!
 */

use Configurator\Configurator;

require(__DIR__ . "/vendor/autoload.php");

try {

    //connect to the mqtt server, listen for topics
    $client = new Mosquitto\Client($clientId);
    $client->connect("mqtt-server", 1883, 60);

    //create configurator
    $configurator = new Configurator();
    $configurator->showUI();

} catch (\Exception $e) {

    echo "
        <div style='padding:10px; background:red; color:white;'>
            Exception: <br><br.
            <b>" . htmlspecialchars($e->getMessage()) . "</b><br><br>
            Stack trace:<br>
            <pre>" . $e . "</pre>
        </div>";
}

