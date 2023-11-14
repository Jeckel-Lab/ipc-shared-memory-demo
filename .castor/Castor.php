<?php

namespace Castor;

use Castor\Attribute\AsContext;

#[AsContext(name: 'docker_context')]
function create_docker_context(): Context
{
    return new Context([
        'docker_compose_files' => ['docker-compose.yml']
    ]);
}

function docker_compose(string $command, ?int $timeout = null): void
{
    $context = context('docker_context');
    $filesArg = array_map(
        static fn(string $file): string => '-f ' . $context->currentDirectory . '/' . $file,
        $context['docker_compose_files']
    );
    run(
        command: sprintf('docker-compose %s %s', implode(' ', $filesArg), $command),
        timeout: $timeout
    );
}

function docker_compose_isRunning(string $container): int
{
    $context = context('docker_context');
    $filesArg = array_map(
        static fn(string $file): string => '-f ' . $context->currentDirectory . '/' . $file,
        $context['docker_compose_files']
    );
    $process = run(
        command: sprintf('docker-compose %s ps | grep Up | grep %s | wc -l', implode(' ', $filesArg), $container),
        quiet: true
    );
    return ((int) $process->getOutput()) === 1;
}

function docker_compose_logs(string $container = ''): void
{
    docker_compose(
        command: sprintf('logs -f %s', $container),
        timeout: 0
    );
}
