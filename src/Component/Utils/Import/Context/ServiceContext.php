<?php

namespace App\Component\Utils\Import\Context;

class ServiceContext implements Context
{
    private $service;
    private $config;

    public function getService()
    {
        return $this->service;
    }

    public function setService($service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config): static
    {
        $this->config = $config;

        return $this;
    }
}
