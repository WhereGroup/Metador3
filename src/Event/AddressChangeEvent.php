<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AddressChangeEvent extends Event
{
    protected $entity;
    protected $config;

    public function __construct($entity, $config)
    {
        $this->entity = $entity;
        $this->config = $config;
    }

    public function getAddress()
    {
        return $this->entity;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
