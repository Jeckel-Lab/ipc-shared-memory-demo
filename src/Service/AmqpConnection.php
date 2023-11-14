<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 09/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnection
{
    private ?AMQPStreamConnection $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password
    ) {}

    /**
     * @return AMQPStreamConnection
     * @throws Exception
     */
    public function getConnection(): AMQPStreamConnection
    {
        if (null === $this->connection) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password
            );
        }
        return $this->connection;
    }

    /**
     * @return AMQPChannel
     * @throws Exception
     */
    public function getChannel(): AMQPChannel
    {
        return ($this->getConnection())->channel();
    }
}
