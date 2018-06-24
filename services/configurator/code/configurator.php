<?php

require(__DIR__ . "/vendor/autoload.php");

use Docker\Docker;

class Configurator
{
    static function showUI()
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
    }
}

echo $_SERVER['REQUEST_URI'] . "<br>";
Configurator::showUI();
