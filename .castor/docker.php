<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 08/11/2023
 */

declare(strict_types=1);

namespace docker;

use Castor\Attribute\AsTask;
use function Castor\run;
use function php\console;

require_once __DIR__ . '/../vendor/autoload.php';

#[AsTask(description: 'Start docker containers')]
function up(): void {
    run(
        command: 'docker-compose -f docker-compose.yml up',
        timeout: 0
    );
}

#[AsTask(description: 'Stop and clean docker containers')]
function down(): void {
    run(
        command: 'docker-compose -f docker-compose.yml down -v'
    );
}

#[AsTask(description: 'Initialize rabbitmq configuration')]
function init(): void {
    run(
        command: 'docker-compose -f docker-compose.yml up -d rabbitmq'
    );
    run(
        command: 'docker-compose -f docker-compose.yml exec rabbitmq rabbitmqctl import_definitions /scripts/definitions.json'
    );
}
