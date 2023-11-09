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

use function Castor\notify;
use function Castor\run;

#[AsTask(description: 'Execute console command')]
function console(
    string $consoleCommand
): void {
    run(
        command: 'docker-compose -f docker-compose.yml exec demo php -d max_execution_time=0 console.php ' . $consoleCommand,
        timeout: 0
    );
}

#[AsTask(description: 'Load messaged in RabbitMQ', name: 'bulk-send-messages')]
function loadMessages(int $nbMessages): void
{
    run(
        command: 'docker-compose -f docker-compose.yml exec demo php -d max_execution_time=0 console.php demo:bulk-send-messages ' . $nbMessages,
        timeout: 0
    );
    notify(sprintf('%d messages loaded', $nbMessages));
}
