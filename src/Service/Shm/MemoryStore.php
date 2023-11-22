<?php

/**
 * @author: Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at: 22/11/2023
 */

declare(strict_types=1);

namespace JeckelLab\IpcSharedMemoryDemo\Service\Shm;

use JeckelLab\IpcSharedMemoryDemo\ValueObject\MemoryKey;
use RuntimeException;
use SysvSemaphore;
use SysvSharedMemory;

class MemoryStore implements SharedMemoryInterface
{
    private readonly int $token;
    private ?SysvSharedMemory $sharedMemory = null;
    /** @var array<int, SysvSemaphore>  */
    private array $locks = [];

    public function __construct(
        private readonly string $filename = __FILE__,
        private readonly string $project_id = 'a'
    ) {
        $this->token = ftok($this->filename, $this->project_id);
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function set(MemoryKey $key, mixed $value, bool $release = true): void
    {
        shm_put_var($this->getSharedMemory(), $key->value, $value);
        if ($release) {
            $this->release($key);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function get(MemoryKey $key, mixed $default, bool $lock = false): mixed
    {
        if ($lock) {
            sem_acquire($this->getSemaphore($key));
        }
        if (!shm_has_var($this->getSharedMemory(), $key->value)) {
            return $default;
        }
        return shm_get_var($this->getSharedMemory(), $key->value);
    }

    public function release(MemoryKey $key): void
    {
        if (isset($this->locks[$key->value])) {
            sem_release($this->locks[$key->value]);
            unset($this->locks[$key->value]);
        }
    }

    private function getSemaphore(MemoryKey $key): SysvSemaphore
    {
        $semaphore = sem_get($key->value);
        if (false === $semaphore) {
            throw new RuntimeException('Could not get semaphore');
        }
        $this->locks[$key->value] = $semaphore;
        return $semaphore;
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
