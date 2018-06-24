<?php
/**
 * Simple UI to update config and reload docker containers.
 *
 * @FIXME This is just a proof of concept, refactor it!
 */

require(__DIR__ . "/vendor/autoload.php");

use Docker\Docker;

class Configurator
{

    /**
     * Show simple UI
     */
    static function showUI()
    {
        echo "Main menu:<hr>";
        echo "[<a href='/reloadContainers'>reload containers</a>]";
    }

    /**
     * Reload all containers except for this one with configurator
     */
    static function reloadContainers()
    {
        echo "Reloading containers... <hr>";

        $docker = new Docker();
        $manager = $docker->getContainerManager();
        $containers = $manager->findAll();

        foreach ($containers as $container) {
            $containerNames = join(",", $container->getNames());
            if (!preg_match("/configurator/", $containerNames)) {
                echo "Restarting container $containerNames ...<br>";
                flush();
                $manager->restart($container->getId());
            }

        }

        echo "<hr>Containers successfully reloaded.";

    }
}

//simple routing, simple web interface
$requestUrl = $_SERVER['REQUEST_URI'];
//echo "Current URL: ". $requestUrl . "<br>";
switch ($requestUrl) {
    case "/reloadContainers":
        Configurator::reloadContainers();
        break;
    default:
        Configurator::showUI();
}

