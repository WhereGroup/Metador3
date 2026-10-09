<?php

namespace App\Service\Search;

use App\Entity\Address;
use App\Service\Logger;
use SolrClientException;
use SolrIllegalArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SolrAddress extends SolrAbstract
{

    /**
     * @throws SolrIllegalArgumentException
     */
    public function __construct(
        #[Autowire(env: 'APP_SOLR_HOST')] $host,
        #[Autowire(env: 'int:APP_SOLR_PORT')] $port,
        #[Autowire(env: 'APP_SOLR_ADDRESS_PATH')] $path,
        Logger $logger
    ) {
        parent::__construct($host, $port, $path, $logger);
    }

    public function update($address, $options = []): void
    {
        if (!$this->isActive()) {
            return;
        }
        /* @var Address $address */
        $doc       = $this->newDocument();
        $doc->addField('id', $address->getId());
        $doc->addField('uuid', $address->getUuid());
        $doc->addField('individualName', $address->getIndividualName());
        $doc->addField('organisationName', $address->getOrganisationName());
        $doc->addField('department', $address->getDepartment());
        $doc->addField('positionName', $address->getPositionName());
        $doc->addField('email', $address->getEmail());
        $doc->addField('country', $address->getCountry());
        $doc->addField('administrativeArea', $address->getAdministrativeArea());
        $doc->addField('deliveryPoint', $address->getDeliveryPoint());
        $doc->addField('city', $address->getCity());
        $doc->addField('postalCode', $address->getPostalCode());
        $doc->addField('voice', $address->getVoice());
        $doc->addField('facsimile', $address->getFacsimile());
        $doc->addField('url', $address->getUrl());
        $doc->addField('urlDescription', $address->getUrlDescription());
        $doc->addField('hoursOfService', $address->getHoursOfService());
        $doc->addField('searchfield', $address->getSearchfield());

        try {
            $this->client->addDocument($doc);
        } catch (SolrClientException $e) {
            $this->log(
                $address->getUuid(),
                'Solr gab beim indizieren von ' . $address->getUuid() . ' folgenden Fehler. ' . $e->getMessage(),
                $options['flush']
            );
        }
    }

    protected function log($uuid, $message, $flush): void
    {
        $log = $this->logger->newLog();
        $log
            ->setType('warning')
            ->setCategory('Solr')
            ->setSubcategory('Address')
            ->setOperation('index')
            ->setIdentifier($uuid)
            ->setMessage($message)
        ;
        $this->logger->set($log, $flush);
    }
}
