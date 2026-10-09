<?php

namespace App\Service;

use App\Event\HealthCheckEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HealthCheck
{
    private EventDispatcherInterface $eventDispatcher;
    private TranslatorInterface $translatorInterface;

    public function __construct(EventDispatcherInterface $eventDispatcher, TranslatorInterface $translatorInterface)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->translatorInterface = $translatorInterface;
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->eventDispatcher, $this->translatorInterface);
    }

    public function check() : HealthCheckEvent
    {
        $event = new HealthCheckEvent($this->translatorInterface);

        $this->eventDispatcher->dispatch($event, 'application.health-check');

        return $event;
    }
}
