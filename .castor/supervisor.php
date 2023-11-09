<?php

declare(strict_types=1);

namespace supervisor;

use Castor\Attribute\AsTask;

use function Castor\run;

require_once __DIR__ . '/../vendor/autoload.php';

#[AsTask(description: 'Connect to supervisorctl')]
function ctl(): void {
    run(
        command: 'docker-compose -f docker-compose.yml exec demo supervisorctl',
        timeout: 0
    );
}
