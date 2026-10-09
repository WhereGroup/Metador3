<?php

namespace App\Event;

use App\Entity\Log;
use Symfony\Contracts\EventDispatcher\Event;

class LoggingEvent extends Event
{
    /** @var  Log $log */
    private $log;

    private $flush;

    public function __construct($log, bool $flush = true)
    {
        $this->log = $log;
        $this->flush = $flush;
    }

    public function __destruct()
    {
        unset($this->log);
    }

    public function getLog(): Log
    {
        return $this->log;
    }

    public function getFlush(): bool
    {
        return $this->flush;
    }
}
