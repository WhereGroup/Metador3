<?php

namespace App\Subscriber;

use App\Service\Configuration;
use App\Event\ApplicationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PluginApplicationSubscriber
 * @package App\Subscriber
 */
class PluginApplicationSubscriber implements EventSubscriberInterface
{
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
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

        if ($app->routeStartsWith('metador_admin') && in_array('plugin', $conf)) {
            $app->add(
                $app->get('AdminMenu', 'plugin')
                    ->icon('icon-puzzle-piece')
                    ->label('Plugins')
                    ->path('metador_admin_plugin')
//                    ->active($app->isBundle('plugin'))
                    ->active($app->routeStartsWith('metador_admin_plugin'))
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            );
        }
    }
}

