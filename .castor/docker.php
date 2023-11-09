<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 08/11/2023
 */

declare(strict_types=1);

namespace docker;

use Castor\Attribute\AsTask;

use function Castor\notify;
use function Castor\run;

require_once __DIR__ . '/../vendor/autoload.php';

#[AsTask(description: 'Start docker containers', aliases: ['u', 'up'])]
function up(): void {
    docker_compose(command: 'up -d');
}

#[AsTask(description: 'Stop and clean docker containers', aliases: ['d', 'down'])]
function down(): void {
    docker_compose('down -v');
}

#[AsTask(description: 'Watch docker containers logs')]
function logs(): void {
    docker_compose('logs -f', timeout: 0);
}

#[AsTask(description: 'Initialize rabbitmq configuration')]
function init(): void {
    docker_compose_exec('rabbitmq', 'rabbitmqctl import_definitions /scripts/definitions.json');
    notify('RabbitMQ Initialized');
}

function docker_compose(
    string $command,
    ?int $timeout = null
): void {
    run(
        command: 'docker-compose -f docker-compose.yml ' . $command,
        timeout: $timeout
    );
}

function docker_compose_exec(string $container, string $command, ?int $timeout = null): void
{
    docker_compose("up -d $container");
    docker_compose("exec $container $command", $timeout);
}
