#!/usr/bin/env php
<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 30/10/2023
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use JeckelLab\IpcSharedMemoryDemo\Console\LoadMessages;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new LoadMessages());

$application->run();
