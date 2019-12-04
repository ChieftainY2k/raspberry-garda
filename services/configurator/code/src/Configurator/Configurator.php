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
     * @param array $requestData
     */
    public function checkAccess(array $requestData)
    {
        $correctPassword = getenv("KD_UI_PASSWORD");
        $userPassword = $requestData['userPassword'] ?? null;
        if ($correctPassword !== $userPassword) {
            echo "
                <html>
                <head>
                    <title>Configurator (" . getenv("KD_SYSTEM_NAME") . ")</title>
                </head>
                <body>
                    <form action='' method='post'>
                    Password: <input type='password' name='userPassword' value='" . htmlspecialchars($userPassword) . "'>
                    <input type='submit' value='submit'>
                    </form>
                </body>
            ";
            exit;
        }
    }


    /**
     * Process request data
     * @param array $requestData
     * @throws \Exception
     */
    public function processRequestData(array $requestData)
    {
        if (!empty($requestData['configAsText'])) {
            $this->saveServicesConfig($requestData['configAsText']);
        }
        if (!empty($requestData['doReloadContainers'])) {
            $this->reloadContainers();
        }
    }

    /**
     * Show simple UI
     * @param array $requestData
     */
    public function showUI(array $requestData)
    {
        $currentConfig = file_get_contents("/service-configs/services.conf");
        echo "
            <html>
            <head>
                <title>Configurator (" . getenv("KD_SYSTEM_NAME") . ")</title>
            </head>
            <body>
                <form action='' method='post'>
                
                Services configuration file:<br>
                <textarea name='configAsText' style='width:100%; height:80%;'>" . htmlspecialchars($currentConfig) . "</textarea>
                
                <input type='checkbox' name='doReloadContainers' value='1'>reload services after config is updated<br>  
                <input type='hidden' name='userPassword' value='" . htmlspecialchars($requestData['userPassword']) . "'>
                
                <input type='submit' value='save config' onclick=\"alert('This may take a while...')\">
                
                </form>
            </body>
            </html>
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

        //@TODO Make sure local system is not "remote" (reserved name)

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
            } elseif (preg_match("/^([a-z0-9_]+)[=]([a-z0-9@_.-]+[ ]*)(#.*)?$/i", $line, $match)) {
                //key=val without quotes and possible comment at the end
                $key = $match[1];
                $value = $match[2];
            } elseif (preg_match("/^([a-z0-9_]+)[=][\"]([a-z0-9@_. -]+)[\"][ ]*(#.*)?$/i", $line, $match)) {
                //key=val without quotes and possible comment at the end
                $key = $match[1];
                $value = $match[2];
            } elseif (preg_match("/^([a-z0-9_]+)[=]({.*})$/i", $line, $match)) {
                //value is json object or json array
                $key = $match[1];
                $value = $match[2];
                if (is_null(json_decode($value))) {
                    throw new \InvalidArgumentException("Invalid format for line $line , invalid JSON value");
                }
            } else {
                //line format unrecognized
                throw new \InvalidArgumentException("Invalid format for line $line , must be KEY=VAL or KEY=\"VAL\" or # (comment) or valid JSON");
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
        ]), 1, false);

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
            if (!preg_match("#configurator|nginx|ngrok|portainer#i", $containerNames)) {
                echo "Restarting container $containerNames ...<br>";
                $manager->restart($container->getId());
            } else {
                echo "Skipping container $containerNames ...<br>";
            }

        }
        echo "Containers successfully reloaded.<hr>";

        //sleep for a while so that services are available again @FIXME
        sleep(20);

        //publish topic
        $this->getMqttClient()->publish("configurator/containers/reloaded", json_encode([
            "system_name" => getenv("KD_SYSTEM_NAME"),
            "timestamp" => time(),
            "local_time" => date("Y-m-d H:i:s"),
        ]), 1, false);

    }
}
