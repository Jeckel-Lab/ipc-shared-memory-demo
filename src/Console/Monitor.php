<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 15/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use JeckelLab\IpcSharedMemoryDemo\Service\Shm\MemoryQueue;
use JeckelLab\IpcSharedMemoryDemo\Service\Shm\MemoryStore;
use JeckelLab\IpcSharedMemoryDemo\ValueObject\MemoryKey;
use JeckelLab\IpcSharedMemoryDemo\ValueObject\QueueId;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:monitor')]
class Monitor extends Command
{
    /** @var array<int, int> */
    protected array $counts = [];

    public function __construct(
        private readonly MemoryQueue $memoryQueue,
        private readonly MemoryStore $memoryStorage
    ) {
        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->memoryQueue->consume(
            handler: fn(string $message, int $messageType) => $this->handleMessage($message),
            messageTypeFilter: QueueId::MONITOR
        );
        return Command::SUCCESS;
    }

    /**
     * @throws JsonException
     */
    protected function handleMessage(string $message): void
    {
        printf("%s\n", $message);
        /** @var array{type: string, pid: int, queue?: string, count?: int} $decodedMessage */
        $decodedMessage = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        if ($decodedMessage['type'] !== 'count' || !isset($decodedMessage['count'])) {
            return;
        }
        $this->counts[$decodedMessage['pid']] = $decodedMessage['count'];
        $this->memoryStorage->set(MemoryKey::COUNT, $this->counts);
    }
}
