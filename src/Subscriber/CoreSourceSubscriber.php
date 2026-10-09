<?php

namespace App\Subscriber;

use App\Event\SourceEvent;
use App\Service\Metadata;
use App\Entity\Metadata as MetadataEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CoreSourceSubscriber
 * @package App\Subscriber
 */
class CoreSourceSubscriber implements EventSubscriberInterface
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * CoreSourceSubscriber constructor.
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
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
        $count = $this->metadata->db->getRepository()->countBySource($event->getSlug());

        if ($count > 0) {
            $event->addMessage("Es werden %count% Metadaten gelöscht.", ['%count%' => $count]);
        }
    }

    /**
     * @param SourceEvent $event
     */
    public function onPostDelete(SourceEvent $event): void
    {
        $repo = $this->metadata->db->getRepository();

        /** @var MetadataEntity $entity */
        foreach ($repo->findBy(['source' => $event->getSlug()]) as $entity) {
            $this->metadata->delete($entity);
        }
    }
}

