<?php

namespace App\Component\Utils;

use DateTime;
use Exception;

class Stopwatch
{
    protected $start;
    protected $stop;

    public function __construct()
    {
        list($usec, $sec) = explode(" ", microtime());
        $this->start = ((float)$usec + (float)$sec);
    }

    public function stop(): array
    {
        list($usec, $sec) = explode(" ", microtime());
        $this->stop = ((float)$usec + (float)$sec);

        return $this->getDuration();
    }

    public function duration(): string
    {
        return (string)round($this->stop - $this->start, 3);
    }

    public function getStartTime($format = 'H:i:s'): string
    {
        return ($this->createDateTime($this->start))->format($format);
    }

    public function getStopTime($format = 'H:i:s'): string
    {
        return ($this->createDateTime($this->stop))->format($format);
    }

    public function getDuration(): array
    {
        $date = ($this->createDateTime($this->start))->diff($this->createDateTime($this->stop));

        return [
            'd' => $date->format('%a'),
            'h' => $date->format('%h'),
            'i' => $date->format('%i'),
            's' => $date->format('%s'),
            'u' => $this->getMilliseconds($this->stop - $this->start)
        ];
    }

    protected function createDateTime($microtime): ?DateTime
    {
        try {
            return new DateTime(date('Y-m-d H:i:s.' . $this->getMilliseconds($microtime), $microtime));
        } catch (Exception $e) {
            return null;
        }
    }

    protected function getMilliseconds($microtime): string
    {
        return sprintf("%06d", ($microtime - floor($microtime)) * 1000000);
    }

    public function __toString()
    {
        $duration = $this->getDuration();

        return '' . $this->getStartTime() . ' - ' . $this->getStopTime() . '. Dauer: '
            . $duration['h'] . ' Stunden, '
            . $duration['i'] . ' Minuten, '
            . $duration['s'] . ' Sekunden, ';
    }
}
