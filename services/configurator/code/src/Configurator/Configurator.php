<?php

namespace Configurator;

use Docker\Docker;

class Configurator
{

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
            $line = trim($line);
            if (
                (!empty($line))
                and !preg_match("/^#.*$/i", $line) //comment
                and !preg_match("/^[a-z0-9_]+[=][a-z0-9@_.-]+[ ]*(#.*)?$/i", $line) //key=val without quotes and possible comment at the end
                and !preg_match('/^[a-z0-9_]+[=]["][a-z0-9@_. -]+["][ ]*(#.*)?$/i', $line) //key=val with quotes and possible comment at the end
            ) {
                throw new \InvalidArgumentException("Invalid format for line $line , must be KEY=VAL or KEY=\"VAL\" or # (comment)");
            }

            list($key, $value) = explode("=", $line);

            //specific key/value validation
            if ($key == "KD_SYSTEM_NAME" and (!preg_match("/^[a-z0-9]+$/i", $value))) {
                throw new \InvalidArgumentException("KD_SYSTEM_NAME must be alphanumeric without spaces");
            }

        });
        //echo "OK";
        //exit;
        $newConfig = join("\n", $configAsTextLines);
        if (!file_put_contents("/service-configs/services.conf", $newConfig)) {
            throw new \Exception("Cannot save config file");
        }
        echo "Services configuration successfully saved.<hr>";
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
