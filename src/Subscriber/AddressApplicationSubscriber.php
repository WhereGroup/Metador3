<?php

namespace App\Subscriber;

use App\Event\ApplicationEvent;
use App\Service\Address;
use App\Service\Configuration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddressApplicationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Address $address,
        private readonly Configuration $configuration,
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
        $conf = $this->configuration->get('administration', 'plugin', 'metador_core', []);

        if (!is_array($conf)) {
            $conf = [];
        }

        if ($app->routeStartsWith('metador_admin') && in_array('address', $conf, true)) {
            $app->add(
                $app->get('AdminMenu', 'address')
                    ->icon('icon-address-book')
                    ->label('Adressen')
                    ->path('metador_admin_address')
                    ->active($app->routeStartsWith('metador_admin_address'))
                    ->setRole('ROLE_SYSTEM_GEO_OFFICE')
            );

            if ($app->isRoute('metador_admin_index')) {
                $app->add(
                    $app->get('AppInformation', 'address-info')
                        ->icon('icon-address-book')
                        ->label('Adressen')
                        ->count($this->address->count())
                        ->setRole('ROLE_SYSTEM_GEO_OFFICE')
                );
            }
        }

        $app->append(
            $app->get('Script')->file('bundles/metadoraddress/address.js')
        );
    }
}
