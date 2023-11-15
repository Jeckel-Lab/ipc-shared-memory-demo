<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 29/10/2023
 */

declare(strict_types=1);

use JeckelLab\IpcSharedMemoryDemo\Service\SharedMemory;
use JeckelLab\IpcSharedMemoryDemo\ValueObject\MemoryKey;

require_once __DIR__ . '/../vendor/autoload.php';

$container = (new DI\ContainerBuilder())
    ->addDefinitions(__DIR__ . '/../config/config.php')
    ->build();

/** @var SharedMemory $memory */
$memory = $container->get(SharedMemory::class);

$counts = $memory->getValue(MemoryKey::COUNT);

header('Content-Type: text/plain; charset=UTF-8');
?>
demo_up 1
<?php
foreach ($counts as $pid => $count) {
    printf("demo_worker_count{pid=\"pid_%d\"} %s\n", $pid, $count);
}
