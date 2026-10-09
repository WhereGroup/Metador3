<?php

namespace App\Subscriber\Import;

use App\Event\SourceEvent;
use App\Repository\ImportExportRepository;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SourceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ImportExportRepository $repo,
        private readonly Plugin $plugin,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'source.confirm_delete' => ['onConfirmDelete', 0],
            'source.post_delete'    => ['onPostDelete', 0],
        ];
    }

    public function onConfirmDelete(SourceEvent $event): void
    {
        if (!$this->plugin->isActive('metador_import')) {
            return;
        }

        $count = $this->repo->countBySource($event->getSlug());

        if ($count > 0) {
            $event->addMessage('Es werden %count% Import/Export-Schnittstellen aufgelöst.', ['%count%' => $count]);
        }
    }

    public function onPostDelete(SourceEvent $event): void
    {
        if (!$this->plugin->isActive('metador_import')) {
            return;
        }

        $this->repo->deleteBySource($event->getSlug());
    }
}

