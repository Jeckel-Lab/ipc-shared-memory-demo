<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 30/10/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use Exception;
use JeckelLab\IpcSharedMemoryDemo\Message\Message;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionBlockedException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:load-messages')]
class LoadMessages extends Command
{
    private ?AMQPStreamConnection $connection = null;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channel = $this->getChannel();

        $exchange = (string) getenv('RABBITMQ_EXCHANGE');

        $batch = 100;
        $max = 1000 * 1000;
        for ($i = 0; $i < $max; $i++) {
            $message = $this->getMessage();
            $channel->batch_basic_publish($message, $exchange, $message->getRoutingKey());

            if ($i % $batch === 0) {
                try {
                    $channel->publish_batch();
                } catch (AMQPConnectionBlockedException) {
                    do {
                        sleep(10);
                    } while ($this->getConnection()->isBlocked());
                    $channel->publish_batch();
                }
                $output->writeln(sprintf('Published %d messages', $i));
            }
        }

        $channel->publish_batch();
        $output->writeln(sprintf('Published %d messages', $max));
        return Command::SUCCESS;
    }

    /**
     * @return AMQPChannel
     * @throws Exception
     */
    protected function getChannel(): AMQPChannel
    {
        return ($this->getConnection())->channel();
    }

    /**
     * @return Message
     * @throws Exception
     */
    protected function getMessage(): Message
    {
        $shard = random_int(0, 9);
        return (new Message(
            sprintf('Hello world, dummy message for shard %s', $shard),
            [
                'content_type' => 'text/plain'
            ]
        ))->setRoutingKey(sprintf('shard-%d', $shard));
    }

    /**
     * @return AMQPStreamConnection
     * @throws Exception
     */
    protected function getConnection(): AMQPStreamConnection
    {
        if (null === $this->connection) {
            $this->connection = new AMQPStreamConnection(
                (string) getenv('RABBITMQ_HOST'),
                (int) getenv('RABBITMQ_PORT'),
                (string) getenv('RABBITMQ_USER'),
                (string) getenv('RABBITMQ_PASS')
            );
        }
        return $this->connection;
    }
}
