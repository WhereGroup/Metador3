<?php

namespace App\Subscriber;

use App\Entity\HealthCheck;
use App\Event\HealthCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BasicHealthcheckSubscriber
 * @package App\Subscriber
 */
abstract class BasicHealthcheckSubscriber implements EventSubscriberInterface
{
    protected string $origin = 'Core';

    /** @var HealthCheckEvent|null $healthCheck  */
    protected $healthCheck = null;

    public static function getSubscribedEvents(): array
    {
        return [
            'application.health-check' => ['add', 0],
        ];
    }

    protected function add($result, $message, $parameter = []): static
    {
        if (is_null($this->healthCheck)) {
            throw new \Exception('HealthCheckEvent not set in subscriber.');
        }

        $check = new HealthCheck();
        $check
            ->setOrigin($this->origin)
            ->setMessage($message)
            ->setParameters($parameter);

        $this->healthCheck->add($check->setResult($result));
        return $this;
    }
}

