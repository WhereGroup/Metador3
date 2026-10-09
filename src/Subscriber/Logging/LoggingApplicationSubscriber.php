<?php

namespace App\Subscriber\Logging;

use App\Service\Plugin;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Event\ApplicationEvent;

/**
 * Class LoggingApplicationSubscriber
 * @package App\Subscriber\Logging
 */
class LoggingApplicationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Plugin $plugin)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'application.loading' => ['onLoading', 0],
        ];
    }

    /**
     * @param ApplicationEvent $event
     * @throws Exception
     */
    public function onLoading(ApplicationEvent $event): void
    {
        if (!$this->plugin->isActive('metador_logging')) {
            return;
        }

        $app = $event->getApplication();

        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'logging')
                    ->icon('icon-book')
                    ->label('Logging')
                    ->path('metador_admin_logging')
                    ->setRole('ROLE_SYSTEM_GEO_OFFICE')
            );
        }
    }
}

