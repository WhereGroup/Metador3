<?php

namespace App\Component\Utils\Import;

use RuntimeException;

class ProcessContext
{
    protected $totalItemCount;
    protected $command;
    protected $partitionSize = 10000;
    protected $range = 1;
    protected $rootDir;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function getCommand(): array
    {
        if ($this->totalItemCount === null) {
            throw new RuntimeException("totalItemCount can not be null.");
        }

        // @codingStandardsIgnoreStart
        return [
            'php', realpath($this->rootDir . '/bin/console'), $this->command,
            '--offset', (string)$this->range,
            '--limit', (string)$this->partitionSize
        ];
        // @codingStandardsIgnoreEnd
    }

    public function getTotalItemCount()
    {
        return $this->totalItemCount;
    }

    public function setTotalItemCount($totalItemCount): static
    {
        $this->totalItemCount = $totalItemCount;

        return $this;
    }

    public function getPartitionSize(): int
    {
        return $this->partitionSize;
    }

    public function setPartitionSize(int $partitionSize): static
    {
        $this->partitionSize = $partitionSize;

        return $this;
    }

    public function getRange(): int
    {
        return $this->range;
    }

    public function setRange(int $range): static
    {
        $this->range = $range;

        return $this;
    }

    public function valid(): bool
    {
        return ($this->range <= $this->totalItemCount);
    }

    public function next(): static
    {
        $this->range += $this->partitionSize;

        return $this;
    }


    public function getRootDir()
    {
        return $this->rootDir;
    }

    public function setRootDir($rootDir): static
    {
        $this->rootDir = $rootDir;

        return $this;
    }
}
