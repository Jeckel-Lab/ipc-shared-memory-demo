<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 22/11/2023
 */

declare(strict_types=1);
declare(ticks=1);

namespace JeckelLab\IpcSharedMemoryDemo\Console;

use JeckelLab\IpcSharedMemoryDemo\Service\WorkerQueueManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:zombie-killer')]
class ZombieKiller extends Command
{
    private bool $stop = false;

    public function __construct(
        private readonly WorkerQueueManager $queueManager
    ) {
        parent::__construct();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        pcntl_signal(SIGTERM, fn() => $this->stop = true);
        while (!$this->stop) {
            $this->queueManager->clearZombies();
            sleep(1);
        }
        return Command::SUCCESS;
    }
}
