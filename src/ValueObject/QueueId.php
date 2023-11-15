<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 15/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\ValueObject;

enum QueueId: int
{
    case MONITOR = 1;
}
