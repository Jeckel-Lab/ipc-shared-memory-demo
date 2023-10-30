<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 30/10/2023
 */

declare(strict_types=1);

namespace php;

require_once __DIR__ . '/../vendor/autoload.php';

use Castor\Attribute\AsTask;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use function Castor\run;

#[AsTask(description: 'Execute console command')]
function console(
    string $consoleCommand
): void {
    run('docker-compose -f docker-compose.yml exec demo php console.php ' . $consoleCommand);
}
