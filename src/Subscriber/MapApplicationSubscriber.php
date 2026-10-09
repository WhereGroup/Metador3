<?php

namespace App\Subscriber;

use App\Event\ApplicationEvent;
use App\Repository\WmsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MapApplicationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WmsRepository $wmsRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'application.loading' => ['onLoading', 0],
        ];
    }

    public function onLoading(ApplicationEvent $event): void
    {
        $app = $event->getApplication();

        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'map')
                    ->icon('icon-map')
                    ->label('Hintergrundkarte')
                    ->path('metador_admin_map')
                    ->setRole('ROLE_SYSTEM_GEO_OFFICE')
                    ->active($app->routeStartsWith('metador_admin_map'))
            );
        }

        if ($app->isRoute('metador_home')) {
            $app->add(
                $app->get('Configuration')
                    ->parameter('map_background', json_encode($this->wmsRepository->all(), JSON_FORCE_OBJECT))
            )
            ->add($app->get('Style')->file('bundles/metadormap/css/map.css'))
            ->add($app->get('Script')->file('bundles/metadormap/js/map.js'))
            ->add($app->get('Script')->file('bundles/metadormap/js/map-ui.js'));
        }
    }
}

