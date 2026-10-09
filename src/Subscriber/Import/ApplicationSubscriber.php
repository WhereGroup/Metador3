<?php

namespace App\Subscriber\Import;

use App\Event\ApplicationEvent;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ApplicationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Plugin $plugin,
    )
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
        if (!$this->plugin->isActive('metador_import')) {
            return;
        }

        $app = $event->getApplication();

        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'import_export')
                    ->icon('icon-cloud-download')
                    ->label('Import/Export')
                    ->path('metador_admin_import')
                    ->setRole('ROLE_SYSTEM_GEO_OFFICE')
                    ->active($app->routeStartsWith('metador_admin_import'))
            );
        }

        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return;
        }

        $profile = $request->attributes->get('profile');

        if (!($app->isBundle('core') && $app->isController('profile') && $profile)) {
            return;
        }

        if ($app->isAction('index')) {
            $app->add(
                $app->get('PluginMenu', 'import')
                    ->label('Import')
                    ->icon('icon-upload')
                    ->path('metador_import_index', ['profile' => $profile])
            );
        } elseif ($app->isAction('edit') && $app->isEnv('dev')) {
            $id = $request->get('id', 0);

            $app->add(
                $app->get('PluginMenu', 'import-test')
                    ->label('Test-Import')
                    ->icon('icon-accessibility')
                    ->path('metador_import_test', [
                        'profile' => $profile,
                        'id'      => $id,
                    ])
            );
        }
    }
}

