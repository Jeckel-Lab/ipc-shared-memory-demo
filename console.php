#!/usr/bin/env php
<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 30/10/2023
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;

$container = (new DI\ContainerBuilder())
    ->addDefinitions(__DIR__ . '/config/config.php')
    ->build();

/** @var Application $application */
$application = $container->get(Application::class);
$application->run();
