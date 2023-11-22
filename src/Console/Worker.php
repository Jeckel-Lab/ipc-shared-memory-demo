<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 09/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use Evenement\EventEmitter;
use JeckelLab\IpcSharedMemoryDemo\Service\AmqpConnection;
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

    public function __construct(
        private readonly AmqpConnection $connection,
        private readonly EventEmitter $emitter,
        private readonly WorkerQueueManager $queueManager
    ) {
        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $this->queueManager->getFreeQueueOrWait();
        $channel = $this->connection->getChannel();
        $channel->basic_qos(0, 10, false);
        $channel->basic_consume(
            queue: $queue,
            callback: fn(AMQPMessage $message) => $this->consume($message, $output)
        );
        $this->emitter->emit('worker.start', ['queue' => $queue]);

        try {
            $channel->consume();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }

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
        $output->writeln(sprintf('Received message: %s', $message->body));
        usleep($decodedMessage['duration']);
        $message->ack();
        $this->count++;

        if ($this->count % 100 === 0) {
            $this->emitter->emit('worker.heartbeat', ['count' => $this->count]);
        }
    }
}
