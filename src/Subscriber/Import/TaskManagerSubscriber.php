<?php

namespace App\Subscriber\Import;

use App\Event\TaskManagerEvent;
use App\Repository\ImportExportRepository;
use App\Service\Import\ImportExport;
use App\Service\Plugin;
use App\Service\Process;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TaskManagerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ImportExport $app,
        private readonly ImportExportRepository $repo,
        private readonly Process $process,
        private readonly Plugin $plugin,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'metador.taskmanager' => ['onTaskmanager', 0],
        ];
    }

    public function onTaskmanager(TaskManagerEvent $event): void
    {
        if (!$this->plugin->isActive('metador_import')) {
            return;
        }

        // Todo: Cron ausbauen
        foreach ($this->app->all() as $conf) {
            $token = $event->getToken();

            // Import
            if (!empty($conf->getImportFolder())) {
                $this->repo->updateDateTime($conf);

                $this->process->runSymfonyCommandInBackground(
                    'metador:import ' . (string) $conf->getId(),
                    'import-' . $conf->getId()
                );
                continue;
            }

            // Export
            if (!empty($conf->getExportFolder())) {
                $event->addMessage('Starte Export');
                $this->repo->updateDateTime($conf);

                $this->process->runSymfonyCommandInBackground(
                    'metador:export ' . (string) $conf->getId(),
                    'export-' . $conf->getId()
                );
            }
        }
    }
}

