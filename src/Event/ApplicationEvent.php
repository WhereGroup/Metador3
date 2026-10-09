<?php

namespace App\Event;



use App\Service\Application;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class ApplicationEvent
 * @package App\Event
 */
class ApplicationEvent extends Event
{
    protected $application;
    protected $config;

    public function __construct(Application $application, $config)
    {
        $this->application = $application;
        $this->config      = $config;
    }

    public function __destruct()
    {
        unset(
            $this->application,
            $this->config
        );
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * @return mixed
     */
    public function getConfig(): mixed
    {
        return $this->config;
    }
}
