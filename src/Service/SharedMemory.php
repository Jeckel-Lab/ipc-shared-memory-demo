<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 15/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service;

use JeckelLab\IpcSharedMemoryDemo\ValueObject\MemoryKey;
use RuntimeException;
use SysvMessageQueue;
use SysvSharedMemory;

class SharedMemory
{
    private ?SysvMessageQueue $queue = null;
    private ?SysvSharedMemory $sharedMemory = null;

    /**
     * @param callable(string $message, int $messageType): void $handler
     * @param int $messageTypeFilter
     * @return void
     */
    public function consume(callable $handler, int $messageTypeFilter = 0): void
    {
        $message = '';
        $messageType = 0;
        while (true) {
            \msg_receive(
                queue: $this->getQueue(),
                desired_message_type: $messageTypeFilter,
                received_message_type: $messageType,
                max_message_size: 4096,
                message: $message,
            );
            $handler($message, $messageType);
        }
    }

    public function publish(string $message, int $messageType = 0): void
    {
        msg_send($this->getQueue(), $messageType, $message);
    }

    public function setValue(MemoryKey $key, mixed $value): void
    {
        shm_put_var($this->getSharedMemory(), $key->value, $value);
    }

    public function getValue(MemoryKey $key): mixed
    {
        return shm_get_var($this->getSharedMemory(), $key->value);
    }

    private function getToken(): int
    {
        return ftok(__FILE__, 'a');
    }

    private function getQueue(): SysvMessageQueue
    {
        if (null === $this->queue) {
            $queue = msg_get_queue($this->getToken());
            if (false === $queue) {
                throw new RuntimeException('Could not get queue');
            }
            $this->queue = $queue;
        }
        return $this->queue;
    }

    private function getSharedMemory(): SysvSharedMemory
    {
        if (null === $this->sharedMemory) {
            $sharedMemory = shm_attach($this->getToken());
            if (false === $sharedMemory) {
                throw new RuntimeException('Could not get shared memory');
            }
            $this->sharedMemory = $sharedMemory;
        }
        return $this->sharedMemory;
    }
}
