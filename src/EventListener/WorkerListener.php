<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 15/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\EventListener;

use Evenement\EventEmitter;
use JeckelLab\IpcSharedMemoryDemo\Service\SharedMemory;
use JeckelLab\IpcSharedMemoryDemo\ValueObject\QueueId;
use JsonException;

readonly class WorkerListener
{
    public function __construct(
        private SharedMemory $memory
    ) {}

    public function registerListener(EventEmitter $emitter): EventEmitter
    {
        $emitter->on('worker.start', fn(string $queue) => $this->onWorkerStarted($queue));
        $emitter->on('worker.stop', fn() => $this->onWorkerStop());
        $emitter->on('worker.heartbeat', fn(int $count) => $this->onWorkerHeartbeat($count));
        return $emitter;
    }

    /**
     * @throws JsonException
     */
    private function onWorkerStarted(string $queue): void
    {
        $this->memory->publish(
            message: json_encode(
                ['type' => 'start', 'pid' => getmypid(), 'queue' => $queue],
                JSON_THROW_ON_ERROR
            ),
            messageType: QueueId::MONITOR
        );
    }

    /**
     * @throws JsonException
     */
    private function onWorkerHeartbeat(int $count): void
    {
        $this->memory->publish(
            message: json_encode(
                ['type' => 'count', 'pid' => getmypid(), 'count' => $count],
                JSON_THROW_ON_ERROR
            ),
            messageType: QueueId::MONITOR
        );
    }

    /**
     * @throws JsonException
     */
    private function onWorkerStop(): void
    {
        $this->memory->publish(
            message: json_encode(
                ['type' => 'stop', 'pid' => getmypid()],
                JSON_THROW_ON_ERROR
            ),
            messageType: QueueId::MONITOR
        );
    }
}
