<?php

namespace App\Subscriber;

use App\Service\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Event\ApplicationEvent;

class UserApplicationSubscriber implements EventSubscriberInterface
{
    protected $requestStack;
    protected $user;

    public function __construct(RequestStack $requestStack, UserInterface $user)
    {
        $this->requestStack = $requestStack;
        $this->user = $user;
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

        // ADMIN AREA
        if ($app->routeStartsWith('metador_admin')) {
            $app->add(
                $app->get('AdminMenu', 'user')
                    ->icon('icon-user')
                    ->label('Benutzer')
                    ->path('metador_admin_user')
                    ->active($app->isController('user'))
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            )->add(
                $app->get('AdminMenu', 'group')
                    ->icon('icon-users')
                    ->label('Gruppen')
                    ->path('metador_admin_group')
                    ->active($app->isController('group'))
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            );

            if ($app->isRoute('metador_admin_index')) {
                $app->add(
                    $app->get('AppInformation', 'user-info')
                        ->icon('icon-user')
                        ->label('Benutzer')
                        ->count($this->user->count())
                        ->setRole('ROLE_SYSTEM_SUPERUSER')
                )->add(
                    $app->get('AppInformation', 'group-info')
                        ->icon('icon-users')
                        ->label('Gruppen')
                        ->count($this->user->countGroups())
                        ->setRole('ROLE_SYSTEM_SUPERUSER')
                );
            }
        }
    }
}

