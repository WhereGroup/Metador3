<?php

namespace App\Subscriber\CatalogueService;

use App\Event\SourceEvent;
use App\Repository\CswRepository;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CatalogueServiceSourceSubscriber
 * @package App\Subscriber\CatalogueService
 */
class CatalogueServiceSourceSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CswRepository $repo, private readonly Plugin $plugin)
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            'source.confirm_delete' => ['onConfirm', 0],
            'source.post_delete' => ['onPostDelete', 0],
        ];
    }

    /**
     * @param SourceEvent $event
     */
    public function onConfirm(SourceEvent $event): void
    {
        if (!$this->plugin->isActive('metador_catalogue_service')) {
            return;
        }

        $count = $this->repo->countBySource($event->getSlug());

        if ($count > 0) {
            $event->addMessage("Es werden %count% CSW-Schnittstellen aufgelöst.", ['%count%' => $count]);
        }
    }

    /**
     * @param SourceEvent $event
     */
    public function onPostDelete(SourceEvent $event): void
    {
        if (!$this->plugin->isActive('metador_catalogue_service')) {
            return;
        }

        $this->repo->deleteBySource($event->getSlug());
    }
}

