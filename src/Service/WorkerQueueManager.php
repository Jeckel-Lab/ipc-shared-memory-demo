<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 22/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service;

use JeckelLab\IpcSharedMemoryDemo\Service\Shm\MemoryStore;
use JeckelLab\IpcSharedMemoryDemo\ValueObject\MemoryKey;
use RuntimeException;

class WorkerQueueManager
{
    /**
     * @var array<int, null|int>
     */
    private const DEFAULT_QUEUE_RESERVATION = [
        0 => null,
        1 => null,
        2 => null,
        3 => null,
        4 => null,
        5 => null,
        6 => null,
        7 => null,
        8 => null,
        9 => null
    ];

    public function __construct(private readonly MemoryStore $memoryStore) {}

    public function getFreeQueue(): string|null
    {
        /** @var array<int, null|int> $queueReservation */
        $queueReservation = $this->memoryStore->get(
            key: MemoryKey::QUEUE_RESERVATION,
            default: self::DEFAULT_QUEUE_RESERVATION,
            lock: true
        );

        foreach ($queueReservation as $key => $value) {
            if ($value === null) {
                $pid = getmypid();
                if (!is_int($pid)) {
                    throw new RuntimeException(sprintf(
                        'Unable to get my pid: %s',
                        var_export($pid, true)
                    ));
                }
                $queueReservation[$key] = $pid;
                $this->memoryStore->set(
                    key: MemoryKey::QUEUE_RESERVATION,
                    value: $queueReservation,
                    release: true
                );
                return 'demo.Q.incoming.shard_' . $key;
            }
        }
        $this->memoryStore->release(MemoryKey::QUEUE_RESERVATION);
        return null;
    }

    public function releaseQueue(string $queue): void
    {
        $queueId = (int) substr($queue, 0, -1);

        /** @var array<int, null|int> $queueReservation */
        $queueReservation = $this->memoryStore->get(
            key: MemoryKey::QUEUE_RESERVATION,
            default: self::DEFAULT_QUEUE_RESERVATION,
            lock: true
        );

        $queueReservation[$queueId] = null;
        $this->memoryStore->set(
            key: MemoryKey::QUEUE_RESERVATION,
            value: $queueReservation,
            release: true
        );
    }
}
