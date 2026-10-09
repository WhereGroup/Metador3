<?php

namespace App\Subscriber;

use App\Event\HealthCheckEvent;
use App\Event\MetadataFlushEvent;
use App\Service\Database;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CoreMetadataFlushSubscriber
 * @package App\Subscriber
 */
class CoreMetadataFlushSubscriber implements EventSubscriberInterface
{
    private $db;

    /**
     * CoreMetadataFlushSubscriber constructor.
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'metadata.flush' => ['onFlush', 0],
        ];
    }

    /**
     * @param MetadataFlushEvent $event
     */
    public function onFlush(MetadataFlushEvent $event): void
    {
        $this->db->flush();
    }
}

