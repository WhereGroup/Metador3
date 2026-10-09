<?php

namespace App\Subscriber;

use App\Event\AddressChangeEvent;
use App\Service\Search\SolrAddress;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class SolrAddressSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SolrAddress $solr)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'address.post_save'  => ['onPostSave', 0],
            'address.pre_delete' => ['onPreDelete', 0],
        ];
    }

    public function onPostSave(AddressChangeEvent $event): void
    {
        $this->solr->update($event->getAddress(), $event->getConfig());
        $this->solr->client->commit();
    }

    public function onPreDelete(AddressChangeEvent $event): void
    {
        $this->solr->client->deleteByQuery('id:' . $event->getAddress()->getId());
        $this->solr->client->commit();
    }
}
