<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 29/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service\Task;

use RuntimeException;

class UpdateStock
{
    public function __construct() {}

    public function update(int $duration): void
    {
        if (($err = random_int(0, 100)) > 90) {
            throw new RuntimeException("Something bad happened with probability $err");
        }

        usleep($duration);
    }
}
