<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 09/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use JeckelLab\IpcSharedMemoryDemo\Service\AmqpConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:worker')]
class Worker extends Command
{
    public function __construct(private readonly AmqpConnection $connection)
    {
        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = 'demo.Q.incoming.shard_' . random_int(0, 10);
        $channel = $this->connection->getChannel();
        $channel->basic_qos(0, 10, false);
        $channel->basic_consume(
            queue: $queue,
            callback: fn(AMQPMessage $message) => $this->consume($message, $output)
        );

        try {
            $channel->consume();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }
        return Command::SUCCESS;
    }

    protected function consume(AMQPMessage $message, OutputInterface $output): void
    {
        /** @var array{duration: int} $decodedMessage */
        $decodedMessage = json_decode($message->body, true);
        $output->writeln(sprintf('Received message: %s', $message->body));
        usleep($decodedMessage['duration']);
        $message->ack();
    }
}
