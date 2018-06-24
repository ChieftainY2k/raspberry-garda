<?php

use Docker\Docker;

require(__DIR__ . "/vendor/autoload.php");


class Configurator
{
    static function showUI()
    {
        echo "I'm the configurator!<br><br>";
        $docker = Docker::create();
        $containers = $docker->containerList();

        foreach ($containers as $container) {
            var_dump($container->getNames());
        }
    }
}


Configurator::showUI();