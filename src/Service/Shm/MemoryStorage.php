<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 22/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service\Shm;

use JeckelLab\IpcSharedMemoryDemo\ValueObject\MemoryKey;
use RuntimeException;
use SysvSharedMemory;

class MemoryStorage implements SharedMemoryInterface
{
    private readonly int $token;
    private ?SysvSharedMemory $sharedMemory = null;

    public function __construct(
        private readonly string $filename = __FILE__,
        private readonly string $project_id = 'a'
    ) {
        $this->token = ftok($this->filename, $this->project_id);
    }

    public function setValue(MemoryKey $key, mixed $value): void
    {
        shm_put_var($this->getSharedMemory(), $key->value, $value);
    }

    public function getValue(MemoryKey $key, mixed $default): mixed
    {
        if (!shm_has_var($this->getSharedMemory(), $key->value)) {
            return $default;
        }
        return shm_get_var($this->getSharedMemory(), $key->value);
    }

    private function getSharedMemory(): SysvSharedMemory
    {
        if (null === $this->sharedMemory) {
            $sharedMemory = shm_attach($this->token);
            if (false === $sharedMemory) {
                throw new RuntimeException('Could not get shared memory');
            }
            $this->sharedMemory = $sharedMemory;
        }
        return $this->sharedMemory;
    }
}
