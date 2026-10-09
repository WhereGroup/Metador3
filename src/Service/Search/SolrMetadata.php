<?php

namespace App\Service\Search;

use App\Component\Utils\GeomTools;
use App\Entity\Address;
use App\Entity\Metadata;
use App\Service\Logger;
use DateTime;
use SolrClientException;
use SolrIllegalArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SolrMetadata extends SolrAbstract
{

    /**
     * @throws SolrIllegalArgumentException
     */
    public function __construct(
        #[Autowire(env: 'APP_SOLR_HOST')]          $host,
        #[Autowire(env: 'int:APP_SOLR_PORT')]      $port,
        #[Autowire(env: 'APP_SOLR_METADATA_PATH')] $path,
        Logger                                     $logger
    )
    {
        parent::__construct($host, $port, $path, $logger);
    }

    public function update($metadata, $options = []): void
    {
        if (!$this->isActive()) {
            return;
        }
        /* @var Metadata $metadata */
        $doc = $this->newDocument();
        $p = $metadata->getObject();
        $date = $metadata->getDate();
        $dateStamp = $metadata->getDateStamp();

        $doc->addField('id', $metadata->getId());
        $doc->addField('abstract', $metadata->getAbstract());

        if ($date instanceof DateTime) {
            $doc->addField('date', $date->format('Y-m-d'));
        }

        if ($dateStamp instanceof DateTime) {
            $doc->addField('dateStamp', $dateStamp->format('Y-m-d'));
        }

        if (isset($p['keywords']['produktgruppe']['keywords']) && is_array($p['keywords']['produktgruppe']['keywords'])) {
            $doc->addField('productGroup', implode(";", $p['keywords']['produktgruppe']['keywords']));
        }

        /* @var Address $address */
        foreach ($metadata->getAddress() as $address) {
            $doc->addField('address_id', $address->getId());
        }
        if (count($metadata->getAddress()) === 0) {
            $doc->addField('address_id', '');
        }
        $doc->addField('hierarchyLevel', $metadata->getHierarchyLevel());
        $doc->addField('keywords', $metadata->getKeywords());
        $doc->addField('topicCategory', $metadata->getTopicCategory());
        $doc->addField('object', json_encode($p));
        $doc->addField('parent', $metadata->getParent());
        $doc->addField('profile', $metadata->getProfile());
        $doc->addField('public', $metadata->getPublic());
        $doc->addField('searchfield', $metadata->getSearchfield());
        $doc->addField('anyText', $metadata->getAnytext());
        $doc->addField('source', $metadata->getSource());
        $doc->addField('title', $metadata->getTitle());
        $doc->addField('uuid', $metadata->getId());
        $doc->addField('insertUsername', $p['_insert_user']);
        $doc->addField('insertTime', $p['_insert_time']);

        if (isset($p['_groups']) && is_array($p['_groups'])) {
            $doc->addField('role', implode(' ', $p['_groups']));
        }

        if (!empty($metadata->getBboxn()) && !empty($metadata->getBboxe())
            && !empty($metadata->getBboxs()) && !empty($metadata->getBboxw())
        ) {
            $doc->addField('bboxn', $metadata->getBboxn());
            $doc->addField('bboxe', $metadata->getBboxe());
            $doc->addField('bboxs', $metadata->getBboxs());
            $doc->addField('bboxw', $metadata->getBboxw());
        }
        $wkt = false;
        if (isset($p['polygon']['posList']) && !empty($p['polygon']['posList'])) {
            $wkt = GeomTools::polygonToWkt($p['polygon']['posList']);
            $srs = isset($p['polygon']['srsName']) ? (int)$p['polygon']['srsName'] : '';
            if ($srs !== 4326) {
                // @codingStandardsIgnoreStart
                $this->log(
                    $p['_uuid'],
                    'Es wird nur EPSG 4326 unterstützt. Datensatz ' . $p['_uuid'] . ' hatte ' . (string)$srs . ' angegeben',
                    $options['flush']
                );
                // @codingStandardsIgnoreEnd
            } elseif ($wkt === false) {
                // @codingStandardsIgnoreStart
                $this->log(
                    $p['_uuid'],
                    'Polygon im Datensatz ' . $p['_uuid'] . ' scheint felerhaft zu sein. Es wurde ' . $p['polygon']['posList'] . ' angegeben',
                    $options['flush']
                );
                // @codingStandardsIgnoreEnd
            }
        }
        // set from bbox
        if ($wkt === false && !empty($metadata->getBboxn()) && !empty($metadata->getBboxe())
            && !empty($metadata->getBboxs()) && !empty($metadata->getBboxw())) {
            $wkt = GeomTools::polygonWktFromBbox($p['bbox']['wLongitude'], $p['bbox']['sLatitude'], $p['bbox']['eLongitude'], $p['bbox']['nLatitude']);
        }
        if ($wkt !== false) {
            $doc->addField('geom', $wkt);
        }
        try {
            $this->client->addDocument($doc);
        } catch (SolrClientException $e) {
            $this->log(
                $p['_uuid'],
                'Solr gab beim indizieren von ' . $p['_uuid'] . ' folgenden Fehler. ' . $e->getMessage(),
                $options['flush']
            );
        }
    }

//    private static function polygonWktFromBbox(array $p)
//    {
//        try {
//            $n = $p['bbox']['nLatitude'];
//            $s = $p['bbox']['sLatitude'];
//            $w = $p['bbox']['wLongitude'];
//            $e = $p['bbox']['eLongitude'];
//            $bboxPoly = [$w, $s, $e, $s, $e, $n, $w, $n, $w, $s];
//            return MetadataProcessor::polygonToWkt(implode(" ", $bboxPoly));
//        } catch (Throwable $e) {
//
//        }
//        return false;
//    }

    private function hasErros($items): bool
    {
        return count($items) < 2
            || count($items) % 2 !== 0
            || (int)$items[0] == 1
            || (int)$items[0] == 2;
    }

    protected function log($uuid, $message, $flush): void
    {
        $log = $this->logger->newLog();
        $log
            ->setType('warning')
            ->setCategory('Solr')
            ->setSubcategory('Metadata')
            ->setOperation('index')
            ->setIdentifier($uuid)
            ->setMessage($message);
        $this->logger->set($log, $flush);
    }

}