<?php

namespace Configurator;

use Docker\Docker;
use Mosquitto\Client;

class Configurator
{

    /**
     * @var Client
     */
    private $mqttClient = null;

    /**
     *
     * @param Client $mqttClient
     */
    function __construct(Client $mqttClient)
    {
        $this->setMqttClient($mqttClient);
    }

    /**
     * @return Client
     */
    public function getMqttClient()
    {
        return $this->mqttClient;
    }

    /**
     * @param Client $mqttClient
     * @return Configurator
     */
    public function setMqttClient($mqttClient)
    {
        $this->mqttClient = $mqttClient;
        return $this;
    }

    /**
     * Show simple UI
     */
    public function showUI()
    {
        if (!empty($_REQUEST['configAsText'])) {
            $this->saveServicesConfig($_REQUEST['configAsText']);
            $this->reloadContainers();
        }

        $currentConfig = file_get_contents("/service-configs/services.conf");
        echo "
            Current services configuration:<br>
            <form action='' method='post'>
            <textarea name='configAsText' style='width:100%; height:400px;'>" . htmlspecialchars($currentConfig) . "</textarea>
            <input type='submit' value='save config and reload services'>
            </form>
        ";
    }


    /**
     * Save new configuration file for services
     * @param $configAsText
     * @throws \Exception
     */
    public function saveServicesConfig($configAsText)
    {
        echo "Updating services config...<br>";

        //split to lines, normalize, strip empty space, validate
        $configAsTextLines = explode("\n", $configAsText);
        array_walk($configAsTextLines, function (&$line) {
            $key = null;
            $value = null;
            $line = trim($line);
            if (empty($line)) {
                //empty line
                return;
            } elseif (preg_match("/^#.*$/i", $line)) {
                //comment
                return;
            } elseif (preg_match("/^([a-z0-9_]+)[=]([a-z0-9@_.-]+[ ]*)(#.*)?$/i", $line, $matches)) {
                //key=val without quotes and possible comment at the end
                $key = $matches[1];
                $value = $matches[2];
            } elseif (preg_match("/^([a-z0-9_]+)[=][\"]([a-z0-9@_. -]+)[\"][ ]*(#.*)?$/i", $line, $matches)) {
                //key=val without quotes and possible comment at the end
                $key = $matches[1];
                $value = $matches[2];
            } else {

                //line format unrecognized
                throw new \InvalidArgumentException("Invalid format for line $line , must be KEY=VAL or KEY=\"VAL\" or # (comment)");
            }

            //specific key/value validation
            if ($key == "KD_SYSTEM_NAME" and (!preg_match("/^[a-z0-9]+$/i", $value))) {
                throw new \InvalidArgumentException("KD_SYSTEM_NAME must be alphanumeric without spaces");
            }

        });

        $newConfig = join("\n", $configAsTextLines);
        if (!file_put_contents("/service-configs/services.conf", $newConfig)) {
            throw new \Exception("Cannot save config file");
        }
        echo "Services configuration successfully saved.<hr>";

        //publish topic
        $this->getMqttClient()->publish("configurator/config/updated", json_encode([
            "system_name" => getenv("KD_SYSTEM_NAME"),
            "timestamp" => time(),
            "local_time" => date("Y-m-d H:i:s"),
        ]), 1, true);

        //var_dump($res);
        //echo "OK";
        //exit;


    }

    /**
     * Reload all containers except for the one with configurator
     */
    public function reloadContainers()
    {
        echo "Reloading containers... <br>";

        //init docker API client
        $docker = new Docker();
        $manager = $docker->getContainerManager();
        $containers = $manager->findAll();

        //reload all containers except for the configurator container
        foreach ($containers as $container) {
            $containerNames = join(",", $container->getNames());
            if (!preg_match("/configurator/", $containerNames)) {
                echo "Restarting container $containerNames ...<br>";
                $manager->restart($container->getId());
            }

        }
        echo "Containers successfully reloaded.<hr>";

    }
}
