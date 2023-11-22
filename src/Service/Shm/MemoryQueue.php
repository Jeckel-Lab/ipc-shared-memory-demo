<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 22/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service\Shm;

use JeckelLab\IpcSharedMemoryDemo\ValueObject\QueueId;
use RuntimeException;
use SysvMessageQueue;

class MemoryQueue implements SharedMemoryInterface
{
    private readonly int $token;
    private ?SysvMessageQueue $queue = null;

    public function __construct(
        private readonly string $filename = __FILE__,
        private readonly string $project_id = 'a'
    ) {
        $this->token = ftok($this->filename, $this->project_id);
    }

    /**
     * @param callable(string $message, int $messageType): void $handler
     */
    public function consume(callable $handler, QueueId $messageTypeFilter): void
    {
        $message = '';
        $messageType = 0;
        /** @phpstan-ignore-next-line */
        while (true) {
            msg_receive(
                queue: $this->getQueue(),
                desired_message_type: $messageTypeFilter->value,
                received_message_type: $messageType,
                max_message_size: 4096,
                message: $message,
            );
            $handler($message, $messageType);
        }
    }

    public function publish(string $message, QueueId $messageType): void
    {
        msg_send($this->getQueue(), $messageType->value, $message);
    }

    private function getQueue(): SysvMessageQueue
    {
        if (null === $this->queue) {
            $queue = msg_get_queue($this->token);
            if (false === $queue) {
                throw new RuntimeException('Could not get queue');
            }
            $this->queue = $queue;
        }
        return $this->queue;
    }
}
