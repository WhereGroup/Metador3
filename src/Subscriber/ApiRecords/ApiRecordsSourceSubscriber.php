<?php

namespace App\Subscriber\ApiRecords;

use App\Event\SourceEvent;
use App\Repository\RecordsRepository;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiRecordsSourceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RecordsRepository $repository,
        private readonly Plugin $plugin,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'source.confirm_delete' => ['onConfirm', 0],
            'source.post_delete' => ['onPostDelete', 0],
        ];
    }

    public function onConfirm(SourceEvent $event): void
    {
        if (!$this->plugin->isActive('metador_api_records')) {
            return;
        }

        $count = $this->repository->countBySource($event->getSlug());

        if ($count > 0) {
            $event->addMessage("Es werden %count% API-Records-Schnittstellen aufgelöst.", ['%count%' => $count]);
        }
    }

    public function onPostDelete(SourceEvent $event): void
    {
        if (!$this->plugin->isActive('metador_api_records')) {
            return;
        }
        $this->repository->deleteBySource($event->getSlug());
    }
}

