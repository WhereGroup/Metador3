<?php

namespace App\Subscriber\ApiRecords;

use App\Event\ApplicationEvent;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiRecordsApplicationSubscriber implements EventSubscriberInterface
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
        if (!$this->plugin->isActive('metador_api_records')) {
            return;
        }

        $app = $event->getApplication();

        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'api_records')
                    ->icon('icon-cloud-upload')
                    ->label('API Records')
                    ->path('metador_admin_api_records')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
                    ->active($app->routeStartsWith('metador_admin_api_records'))
            );
        }

        if ($app->routeStartsWith('manual')) {
            $app->add(
                $app->get('ManualMenu', 'apirecords')
                    ->icon('icon-cloud-download')
                    ->label('API Records')
                    ->path('manual_api_records')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            );
        }
    }
}

