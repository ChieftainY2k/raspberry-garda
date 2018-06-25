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
        if (!empty($_REQUEST['configAsText'])) {
            self::saveServicesConfig($_REQUEST['configAsText']);
            self::reloadContainers();
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
     * @throws Exception
     */
    static function saveServicesConfig($configAsText)
    {
        echo "Updating services config...<br>";

        //split to lines, normalize, strip empty space, validate
        $configAsTextLines = explode("\n", $configAsText);
        array_walk($configAsTextLines, function (&$line) {
            //@TODO validate the line syntax for values with spaces
            $line = trim($line);
        });
        $newConfig = join("\n", $configAsTextLines);
        if (!file_put_contents("/service-configs/services.conf", $newConfig)) {
            throw new Exception("Cannot save config file");
        }
        echo "Services configuration successfully saved.<hr>";
    }

    /**
     * Reload all containers except for this one with configurator
     */
    static function reloadContainers()
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

Configurator::showUI();

////simple routing, simple web interface
//$requestUrl = $_SERVER['REQUEST_URI'];
////echo "Current URL: ". $requestUrl . "<br>";
//switch ($requestUrl) {
//    case "/reloadContainers":
//        Configurator::reloadContainers();
//        break;
//    default:
//        Configurator::showUI();
//}
//
