<?php

namespace App\Subscriber\CatalogueService;

use App\Event\ApplicationEvent;
use App\Service\Plugin;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CatalogueServiceApplicationSubscriber
 * @package App\Subscriber\CatalogueService
 */
class CatalogueServiceApplicationSubscriber implements EventSubscriberInterface
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
        if (!$this->plugin->isActive('metador_catalogue_service')) {
            return;
        }

        $app = $event->getApplication();

        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'csw')
                    ->icon('icon-cloud-upload')
                    ->label('Catalogue Service')
                    ->path('metador_admin_csw')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
                    ->active($app->routeStartsWith('metador_admin_csw'))
            );
        }

        if ($app->routeStartsWith('manual')) {
            $app->add(
                $app->get('ManualMenu', 'csw')
                    ->icon('icon-cloud-download')
                    ->label('Catalogue Service')
                    ->path('manual_csw')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            );
        }
    }
}

