<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 08/11/2023
 */

declare(strict_types=1);

namespace docker;

use Castor\Attribute\AsTask;

use function Castor\docker_compose;
use function Castor\docker_compose_isRunning;
use function Castor\docker_compose_logs;
use function Castor\notify;

require_once __DIR__ . '/../vendor/autoload.php';

#[AsTask(description: 'Start docker containers', aliases: ['u', 'up'])]
function up(): void
{
    docker_compose(command: 'up -d');
}

#[AsTask(description: 'Stop and clean docker containers', aliases: ['d', 'down'])]
function down(): void
{
    docker_compose('down -v');
}

#[AsTask(description: 'Watch docker containers logs')]
function logs(string $container = ''): void
{
    docker_compose_logs($container);
}

#[AsTask(description: 'Initialize rabbitmq configuration')]
function init(): void
{
    if (! docker_compose_isRunning('rabbitmq')) {
        up();
    }
    docker_compose_exec('rabbitmq', 'rabbitmqctl import_definitions /scripts/definitions.json');
    notify('RabbitMQ Initialized');
}

function docker_compose_exec(string $container, string $command, ?int $timeout = null): void
{
    docker_compose("exec $container $command", $timeout);
}
