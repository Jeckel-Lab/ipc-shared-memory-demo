<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 30/10/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Message;

use PhpAmqpLib\Message\AMQPMessage;

class Message extends AMQPMessage
{
    private string $routingKey = '';

    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    public function setRoutingKey(string $routingKey): self
    {
        $this->routingKey = $routingKey;
        return $this;
    }
}
