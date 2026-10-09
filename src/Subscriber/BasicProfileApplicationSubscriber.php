<?php

namespace App\Subscriber;

use App\Event\ApplicationEvent;
use App\Service\Configuration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BasicProfileApplicationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Configuration $configuration
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

        if ($app->routeStartsWith('metador_admin')) {
            if (in_array('lists', $conf)) {
                $app->add(
                    $app->get('AdminMenu', 'lists')
                        ->icon('icon-file-text')
                        ->label('Auswahllisten')
                        ->path('metador_admin_lists')
                        ->setRole('ROLE_SYSTEM_GEO_OFFICE')
                        ->active($app->routeStartsWith('metador_admin_lists'))
                );
            }

            if (in_array('keywords', $conf)) {
                $app->add(
                    $app->get('AdminMenu', 'metador_admin_keyword')
                        ->icon('icon-tags')
                        ->label('Schlüsselwörter')
                        ->path('metador_admin_keyword')
                        ->setRole('ROLE_SYSTEM_GEO_OFFICE')
                        ->active($app->routeStartsWith('metador_admin_keyword'))
                );
            }
        }
    }
}
