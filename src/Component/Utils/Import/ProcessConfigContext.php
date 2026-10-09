<?php

namespace App\Component\Utils\Import;

use RuntimeException;
use App\Component\Utils\Import\ProcessContext as BaseClass;

class ProcessConfigContext extends BaseClass
{
    private $id;

    public function getCommand(): array
    {
        if ($this->totalItemCount === null) {
            throw new RuntimeException("totalItemCount can not be null.");
        }

        // @codingStandardsIgnoreStart
        return [
            'php', realpath($this->rootDir . '/bin/console'), $this->command,
            '--offset', (string)$this->range,
            '--limit', (string)$this->partitionSize,
            $this->id
        ];
        // @codingStandardsIgnoreEnd
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }
}
