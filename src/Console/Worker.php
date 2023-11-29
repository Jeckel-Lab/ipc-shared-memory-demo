<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 09/11/2023
 */

declare(strict_types=1);
declare(ticks=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use Evenement\EventEmitter;
use JeckelLab\IpcSharedMemoryDemo\Service\AmqpConnection;
use JeckelLab\IpcSharedMemoryDemo\Service\Task\UpdateStock;
use JeckelLab\IpcSharedMemoryDemo\Service\WorkerQueueManager;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:worker')]
class Worker extends Command
{
    private int $count = 0;
    private int $errorCount = 0;
    private bool $stop = false;

    public function __construct(
        private readonly AmqpConnection $connection,
        private readonly EventEmitter $emitter,
        private readonly WorkerQueueManager $queueManager,
        private readonly UpdateStock $task
    ) {
        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws JsonException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        pcntl_signal(SIGTERM, fn() => $this->stop = true);

        $queue = null;
        while ($queue === null) {
            if ($this->stop) {
                return Command::SUCCESS;
            }
            $queue = $this->queueManager->getFreeQueue();
        }

        $channel = $this->connection->getChannel();
        $channel->basic_qos(0, 10, false);

        $this->emitter->emit('worker.start', ['queue' => $queue]);

        while (!$this->stop) {
            $message = $channel->basic_get($queue);
            if (null === $message) {
                sleep(1);
                continue;
            }
            $this->consume($message, $output);
        }
        $this->queueManager->releaseQueue($queue);
        $this->emitter->emit('worker.stop');
        return Command::SUCCESS;
    }

    /**
     * @throws JsonException
     */
    protected function consume(AMQPMessage $message, OutputInterface $output): void
    {
        /** @var array{duration: int} $decodedMessage */
        $decodedMessage = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
        try {
            $this->task->update($decodedMessage['duration']);
            $message->ack();
        } catch (\Throwable $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
            $message->nack(requeue: true);
            $this->errorCount++;
        }
        $this->count++;

        if ($this->count % 100 === 0) {
            $this->emitter->emit('worker.heartbeat', ['count' => $this->count, 'errorCount' => $this->errorCount]);
        }
    }
}
