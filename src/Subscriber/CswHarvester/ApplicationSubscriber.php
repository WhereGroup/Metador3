<?php

namespace App\Subscriber\CswHarvester;

use App\Event\ApplicationEvent;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ApplicationSubscriber implements EventSubscriberInterface
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

    public function onLoading(ApplicationEvent $event): void
    {
        if (!$this->plugin->isActive('metador_csw_harvester')) {
            return;
        }

        $app = $event->getApplication();

        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'csw-harvester')
                    ->icon('icon-cloud-download')
                    ->label('CSW Harvester')
                    ->path('metador_admin_harvester')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
                    ->active($app->routeStartsWith('metador_admin_harvester'))
            );
        }

        if ($app->routeStartsWith('manual')) {
            $app->add(
                $app->get('ManualMenu', 'csw-harvester')
                    ->icon('icon-cloud-download')
                    ->label('CSW Harvester')
                    ->path('manual_harvester')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            );
        }
    }
}

