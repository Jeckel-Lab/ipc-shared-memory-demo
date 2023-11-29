<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 30/10/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use Exception;
use InvalidArgumentException;
use JeckelLab\IpcSharedMemoryDemo\Message\Message;
use JeckelLab\IpcSharedMemoryDemo\Service\AmqpConnection;
use PhpAmqpLib\Exception\AMQPConnectionBlockedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:bulk-send-messages')]
class SendMessages extends Command
{
    public function __construct(private readonly AmqpConnection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('nb-messages', InputArgument::REQUIRED, 'Number of messages to send');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nbMessages = $this->getNbMessagesToLoad($input);

        $channel = $this->connection->getChannel();
        $exchange = (string) getenv('RABBITMQ_EXCHANGE');

        $batch = 100;
        for ($i = 0; $i < $nbMessages; $i++) {
            $message = $this->getMessage();
            $channel->batch_basic_publish($message, $exchange, $message->getRoutingKey());

            if ($i % $batch === 0) {
                try {
                    $channel->publish_batch();
                } catch (AMQPConnectionBlockedException) {
                    do {
                        sleep(10);
                    } while ($this->connection->getConnection()->isBlocked());
                    $channel->publish_batch();
                }
                $output->writeln(sprintf('Published %d messages', $i));
            }
        }

        $channel->publish_batch();
        $output->writeln(sprintf('Published %d messages', $nbMessages));
        return Command::SUCCESS;
    }

    public function getNbMessagesToLoad(InputInterface $input): int
    {
        $arg = $input->getArgument('nb-messages');
        if (is_numeric($arg)) {
            return (int) $arg;
        }
        throw new InvalidArgumentException('Argument must be a number');
    }

    /**
     * @return Message
     * @throws Exception
     */
    protected function getMessage(): Message
    {
        $shard = random_int(0, 9);
        $duration = random_int(10, 100) * 100; // 10 - 100 µs
        $message = [
            'shard' => $shard,
            'duration' => $duration,
        ];
        return (new Message(
            json_encode($message, JSON_THROW_ON_ERROR),
            [
                'content_type' => 'text/plain'
            ]
        ))->setRoutingKey(sprintf('shard-%d', $shard));
    }
}
