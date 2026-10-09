<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class MetadataFlushEvent
 * @package App\Event
 */
class MetadataFlushEvent extends Event
{
    /** @var  array $conf */
    private $conf;

    /**
     * LoggingEvent constructor.
     * @param $conf
     */
    public function __construct($conf = [])
    {
        $this->conf = $conf;
    }

    public function __destruct()
    {
        unset($this->conf);
    }

    /**
     * @return array
     */
    public function getConf()
    {
        return $this->conf;
    }
}
