<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class MetadataChangeEvent
 * @package App\Event
 */
class MetadataChangeEvent extends Event
{
    protected $dataset;
    protected $config;
    protected $errors;

    public function __construct($dataset, $config)
    {
        $this->dataset = $dataset;
        $this->config  = $config;

        // Set default to true
        $this->config['log']   = $this->config['log']   ?? true;
        $this->config['flush'] = $this->config['flush'] ?? true;
    }

    public function getDataset()
    {
        return $this->dataset;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function addError($message, $params = []): static
    {
        $this->errors[] = ['message' => $message, 'params' => $params];

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
