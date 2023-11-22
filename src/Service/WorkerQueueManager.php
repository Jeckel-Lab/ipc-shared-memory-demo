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
        $queueReservation = $this->getReservations();

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
                $this->updateReservations($queueReservation);
                return 'demo.Q.incoming.shard_' . $key;
            }
        }
        $this->memoryStore->release(MemoryKey::QUEUE_RESERVATION);
        return null;
    }

    public function releaseQueue(string $queue): void
    {
        $queueId = (int) substr($queue, 0, -1);
        $this->releaseQueueId($queueId);
    }

    public function clearZombies(): void
    {
        $queueReservation = $this->getReservations();

        foreach ($queueReservation as $queueId => $pid) {
            if ($pid === null) {
                continue;
            }
            // check if the process is still alive
            if (posix_kill($pid, 0)) {
                continue;
            }
            $this->releaseQueueId($queueId, false);
        }
        $this->memoryStore->release(MemoryKey::QUEUE_RESERVATION);
    }

    /**
     * @return array<int, null|int>
     */
    protected function getReservations(): array
    {
        /** @var array<int, null|int> $queueReservation */
        $queueReservation = $this->memoryStore->get(
            key: MemoryKey::QUEUE_RESERVATION,
            default: self::DEFAULT_QUEUE_RESERVATION,
            lock: true
        );
        return $queueReservation;
    }

    /**
     * @param array<int, null|int> $queueReservation
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function updateReservations(array $queueReservation, bool $release = true): void
    {
        $this->memoryStore->set(
            key: MemoryKey::QUEUE_RESERVATION,
            value: $queueReservation,
            release: $release
        );
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function releaseQueueId(int $queueId, bool $release = true): void
    {
        $queueReservation = $this->getReservations();
        $queueReservation[$queueId] = null;
        $this->updateReservations($queueReservation, $release);
    }
}
