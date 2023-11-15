<?php

use Evenement\EventEmitter;
use JeckelLab\IpcSharedMemoryDemo\Console\Monitor;
use JeckelLab\IpcSharedMemoryDemo\Console\SendMessages;
use JeckelLab\IpcSharedMemoryDemo\Console\Worker;
use JeckelLab\IpcSharedMemoryDemo\EventListener\WorkerListener;
use JeckelLab\IpcSharedMemoryDemo\Service\AmqpConnection;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

return [
    'RABBITMQ_PORT' => DI\env('RABBITMQ_PORT'),
    'RABBITMQ_HOST' => DI\env('RABBITMQ_HOST'),
    'RABBITMQ_USER' => DI\env('RABBITMQ_USER'),
    'RABBITMQ_PASS' => DI\env('RABBITMQ_PASS'),

    Application::class => static function (ContainerInterface $container): Application {
        $application = new Application();
        $application->add($container->get(SendMessages::class));
        $application->add($container->get(Worker::class));
        $application->add($container->get(Monitor::class));
        return $application;
    },
    AmqpConnection::class => static fn(ContainerInterface $container): AmqpConnection => new AmqpConnection(
        host: $container->get('RABBITMQ_HOST'),
        port: (int) $container->get('RABBITMQ_PORT'),
        user: $container->get('RABBITMQ_USER'),
        password: $container->get('RABBITMQ_PASS')
    ),

    EventEmitter::class => static function(ContainerInterface $container): EventEmitter {
        return $container->get(WorkerListener::class)->registerListener(new EventEmitter());
    },
];
