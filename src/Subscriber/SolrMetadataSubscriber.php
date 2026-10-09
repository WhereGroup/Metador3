<?php

namespace App\Subscriber;

use App\Entity\Metadata;
use App\Event\MetadataChangeEvent;
use App\Event\MetadataFlushEvent;
use App\Service\Search\SolrMetadata;
use SolrClientException;
use SolrServerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SolrMetadataSubscriber
 * @package App\Subscriber
 */
class SolrMetadataSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SolrMetadata $solr)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'metadata.post_save'     => ['onPostSave', 0],
            'metadata.post_truncate' => ['onPostTruncate', 0],
            'metadata.pre_delete'    => ['onPreDelete', 0],
            'metadata.flush'         => ['onFlush', 0],
        ];
    }

    public function onPostSave(MetadataChangeEvent $event): void
    {
        $this->solr->update($event->getDataset(), $event->getConfig());
    }

    /**
     * @throws SolrClientException
     * @throws SolrServerException
     */
    public function onPostTruncate(MetadataChangeEvent $event): void
    {
        if ($this->solr->isActive() && $this->solr->isOnline()) {
            $this->solr->client->deleteByQuery('*:*');
            $this->solr->client->commit();
        }
    }

    /**
     * @throws SolrClientException
     * @throws SolrServerException
     */
    public function onPreDelete(MetadataChangeEvent $event): void
    {
        /** @var Metadata $entity */
        $entity = $event->getDataset();
        $this->solr->client->deleteByQuery('uuid:' . $entity->getId());
    }

    public function onFlush(MetadataFlushEvent $event): void
    {
        $this->solr->client->commit();
    }
}
