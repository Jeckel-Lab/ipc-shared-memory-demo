<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 22/11/2023
 */

declare(strict_types=1);

namespace project;

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\run;

#[AsTask(description: 'Start IDE', aliases: ['ide'])]
function ide(): void
{
    run(
        command: sprintf('phpstorm %s &', context()->currentDirectory),
        timeout: 0,
        quiet: true
    );
}
