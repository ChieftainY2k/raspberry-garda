<?php

namespace Configurator;

use Docker\Docker;
use Mosquitto\Client;


/**
 * Class Configurator
 * @package Configurator
 *
 * @FIXME This is just a proof of concept, refactor it!
 */
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
     * @param string $name
     * @return array
     */
    public function getHealthReportForContainer($name)
    {
        $healthReportData = [];
        if (preg_match("#^/garda_(.*)_[0-9]+$#i", $name, $match)) {
            $healthReportFilename = "/data-all/" . $match[1] . "/health-report.json";
            if (file_exists($healthReportFilename)) {
                $content = file_get_contents($healthReportFilename);
                $healthReportData = json_decode($content, true);
            }
        }
        return $healthReportData;
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
                <title>Configurator (" . htmlspecialchars(getenv("KD_SYSTEM_NAME")) . ")</title>
            </head>
            <body>
            <div style='background: #aaffaa; padding:5px; margin:5px; border: solid 1px black;'>
                <b style='font-size:20px'>" . htmlspecialchars(getenv("KD_SYSTEM_NAME")) . "</b>
            </div>
            
            <form action='' method='post' style='display:inline'>
        ";

        //configuration file editor
        echo "
            <div style='background: #efffef; padding:5px; margin:5px; border: solid 1px black;'>
                Services configuration file:<br>
                <textarea name='configAsText' style='width:100%; font-size:11px;' rows='35'>" . htmlspecialchars($currentConfig) . "</textarea>
            </div>
            <div style='background: #efffff; padding:5px; margin:5px; border: solid 1px black;'>
                <input type='checkbox' name='doReloadContainers' value='1'>reload services after config is updated<br>  
                <input type='hidden' name='userPassword' value='" . htmlspecialchars($requestData['userPassword']) . "'>
                <input type='submit' value='save config' x-onclick=\"alert('This may take a while...')\">
            </div>
        ";

        //containers management
        $docker = new Docker();
        $manager = $docker->getContainerManager();
        $containers = $manager->findAll();
        echo "
            <div style='background: #ffffef; padding:5px; margin:5px; border: solid 1px black;'> 
            Services:
            <table border='0' cellpadding='2' cellspacing='1'>
            <tr style='background:#dfdfdf'>
                <td>name</td>
                <td>state</td>
                <td>image</td>
                <td>last health report</td>
            </tr>
        ";
        //reload all containers except for the configurator container
        foreach ($containers as $container) {
            $containerNames = join(",", $container->getNames());
            echo "
                <tr>
                <td>" . $containerNames . "</td>
                <td>" . $container->getState() . "</td>
                <td>" . $container->getImage() . "</td>
                <td>" . htmlspecialchars(json_encode($this->getHealthReportForContainer($containerNames))) . "</td>
                </tr>";
        }
        echo "
            </table>
            </div>
        ";

        echo "
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
            if ($key == "KD_SYSTEM_NAME" and in_array($value, ['remote'])) {
                throw new \InvalidArgumentException("KD_SYSTEM_NAME must not be named 'remote'");
            }
            if ($key == "KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX" and in_array($value, ['remote'])) {
                throw new \InvalidArgumentException("KD_MQTT_BRIDGE_REMOTE_OUT_TOPIC_PREFIX must not be named 'remote'");
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
            echo "Container: <b>" . htmlspecialchars($containerNames) . "</b> : ";
            if (!preg_match("#configurator|ngrok#i", $containerNames)) {
                echo "restarting.";
                $manager->restart($container->getId());
            } else {
                echo "skipping.";
            }

        }
        echo "<br>Containers successfully reloaded.<hr>";

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
