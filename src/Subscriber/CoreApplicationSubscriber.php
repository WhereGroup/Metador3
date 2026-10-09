<?php

namespace App\Subscriber;

use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;
use App\Service\Cache;
use App\Service\Configuration;
use App\Service\Metadata;
use App\Event\ApplicationEvent;

/**
 * Class CoreApplicationSubscriber
 * @package App\Subscriber
 */
class CoreApplicationSubscriber implements EventSubscriberInterface
{
    /**
     * @var Metadata
     */
    private $metadata;
    private $cache;
    private $templating;
    private $configuration;

    /**
     * CoreApplicationSubscriber constructor.
     * @param Metadata $metadata
     * @param Cache $cache
     * @param Environment $templating
     * @param Configuration $configuration
     */
    public function __construct(
        Metadata $metadata,
        Cache $cache,
        Environment $templating,
        Configuration $configuration
    ) {
        $this->metadata = $metadata;
        $this->cache = $cache;
        $this->templating = $templating;
        $this->configuration = $configuration;
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
        $app = $event->getApplication();

        // ADMIN AREA
        if ($app->routeStartsWith('metador_admin')) {
            $conf = $this->configuration->get('administration', 'plugin', 'metador_core', []);
            if (!is_array($conf)) {
                $conf = [];
            }

            $app->add(
                $app->get('AdminMenu', 'index')
                    ->icon('icon-eye')
                    ->label('Übersicht')
                    ->path('metador_admin_index')
                    ->setRole('ROLE_SYSTEM_GEO_OFFICE')
            )->add(
                $app->get('AdminMenu', 'settings')
                    ->icon('icon-wrench')
                    ->label('Einstellungen')
                    ->path('metador_admin_settings')
                    ->setRole('ROLE_SYSTEM_SUPERUSER')
            );

            if (in_array('health', $conf)) {
                $app->add(
                    $app->get('AdminMenu', 'health')
                        ->icon('icon-aid-kit')
                        ->label('Selbsttest')
                        ->path('metador_admin_health')
                        ->setRole('ROLE_SYSTEM_SUPERUSER')
                );
            }

            if (in_array('source', $conf)) {
                $app->add(
                    $app->get('AdminMenu', 'source')
                        ->icon('icon-database')
                        ->label('Datenquellen')
                        ->path('metador_admin_source')
                        ->active($app->routeStartsWith('metador_admin_source'))
                        ->setRole('ROLE_SYSTEM_SUPERUSER')
                );
            }

            $app->add(
                $app->get('AdminMenu', 'pages')
                    ->icon('icon-anchor')
                    ->label('Seiten')
                    ->path('metador_admin_page')
                    ->active($app->routeStartsWith('metador_admin_page'))
                    ->setRole('ROLE_SYSTEM_GEO_OFFICE')
            );

            if ($app->isRoute('metador_admin_index')) {
                $stats = $this->cache->stats();

                if (is_array($stats) && isset($stats[key($stats)]['limit_maxbytes']) && $stats[key($stats)]['bytes']) {
                    $app->add(
                        $app->get('AppInformation', 'cache-info')
                            ->content($this->templating->render('@MetadorCore/Admin/cacheTemplate.html.twig'))
                            ->icon('icon-database')
                            ->label('Cache')
                            ->count($stats[key($stats)]['curr_items'])
                            ->setRole('ROLE_SYSTEM_SUPERUSER')
                    );
                }

                $app->add(
                    $app->get('AppInformation', 'metadata-info')
                        ->icon('icon-database')
                        ->label('Metadaten')
                        ->count($this->metadata->db->getRepository()->countAll())
                        ->setRole('ROLE_SYSTEM_GEO_OFFICE')
                );
            }
        }

        $app->add(
            $app->get('GlobalMenu', 'admin')
                ->icon('icon-cog')
                ->label('Administration')
                ->path('metador_admin_index')
                ->active($app->routeStartsWith('metador_admin'))
                ->setRole('ROLE_SYSTEM_GEO_OFFICE')
        )->add(
            $app->get('GlobalMenu', 'home')
                ->icon('icon-home')
                ->label('Startseite')
                ->path('metador_home')
        );
    }
}

