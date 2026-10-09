<?php

namespace App\Subscriber\Manual;

use App\Component\ApplicationIntegration\ManualMenu;
use App\Event\ApplicationEvent;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ManualApplicationSubscriber
 * @package App\Subscriber\Manual
 */
final class ManualApplicationSubscriber implements EventSubscriberInterface
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
        if (!$this->plugin->isActive('metador_documentation')) {
            return;
        }

        $app = $event->getApplication();

        $app->add(
            $app->get('GlobalMenu', 'book')
                ->icon('icon-book')
                ->label('')
                ->title('Handbuch')
                ->path('manual_index', ['manual' => 'Anwenderhandbuch'])
                ->active($app->routeStartsWith('manual'))
        );

        if ($app->routeStartsWith('manual')) {
            $app->append(
                $app->getIntegrationClass(ManualMenu::class, 'user_manual')
                    ->icon('icon-book')
                    ->label('Anwenderhandbuch')
                    ->path('manual_index')
                    ->active(false)
            )->append(
                $app->getIntegrationClass(ManualMenu::class, 'admin_manual')
                    ->icon('icon-wrench')
                    ->label('Administration')
                    ->path('manual_index', ['manual' => 'Administration'])
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
                    ->active(false)
            );

            if ($app->isEnv('dev')) {
                $app->append(
                    $app->getIntegrationClass(ManualMenu::class, 'dev_manual')
                        ->icon('icon-puzzle-piece')
                        ->label('Entwicklung')
                        ->path('manual_index', ['manual' => 'Entwicklung'])
                        ->active(false)
                );
            }
        }
    }
}

