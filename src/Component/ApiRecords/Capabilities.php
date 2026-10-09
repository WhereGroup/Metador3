<?php

namespace App\Component\ApiRecords;

use App\Entity\Records;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class Capabilities
{

    const FORMAT_JSON = 'application/json';
    const FORMAT_GEOJSON = 'application/geo+json';
//    const FORMAT_HTML = 'text/html';
    const FORMAT_YAML = 'application/yaml';
//    const FORMAT_OPENAPI = 'application/vnd.oai.openapi+json;version=3.0';
    const FORMAT_JSONSCHEMA = 'application/schema+json';

    private $ROUTES;

    private $records;
    private $router;
    private $params;

    public function __construct(Records $records, RouterInterface $router, array $params = [], array $mapping = [])
    {
        $this->records = $records;
        $this->router = $router;
        $this->params = $params;
        $this->ROUTES = $mapping;
    }

    public static function error(string $code, string $description): array
    {
        return [
            'code' => $code,
            'description' => $description,
        ];
    }

    public function addLink(array &$doc, string $href, string $rel, string $format, string $title): void
    {
        $doc['links'][] = [
            'href' => $href,
            'rel' => $rel,
            'type' => $format,
            'title' => $title,
        ];
    }

    public function generateIndex(string $format): array
    {
        $doc = [
            'title' => $this->records->getTitle(),
            'description' => $this->records->getAbstract(),
            'links' => []
        ];

        foreach ($this->getIndexFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['IDX'], array_merge($this->params, ['f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                $_format === $format ? 'self' : 'alternate',
                $_format,
                'This document' . ($_format === $format ? '' : ' as ' . $_format)
            );
        }

        foreach ($this->getIndexFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['CONFORMANCE'], array_merge($this->params, ['f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                'conformance',
                $_format,
                'Conformance declaration as ' . $_format
            );
        }

        foreach ($this->getIndexFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['COLLECTIONS'], array_merge($this->params, ['f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                'data',
                $_format,
                'Collections Metadata as ' . $_format
            );
        }

        return $doc;
    }

    public function getIndexFormats(): array
    {
        return [self::FORMAT_JSON, self::FORMAT_YAML];
    }

    public function generateConformance(string $format, array $conformsTo): array
    {
        $doc = [
            'links' => [],
            'conformsTo' => $conformsTo['conformsTo']
        ];

        foreach ($this->getConformanceFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['CONFORMANCE'], array_merge($this->params, ['f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                $_format === $format ? 'self' : 'alternate',
                $_format,
                'This document' . ($_format === $format ? '' : ' as ' . $_format)
            );
        }

        return $doc;
    }

    public function getConformanceFormats(): array
    {
        return [self::FORMAT_JSON, self::FORMAT_YAML];
    }

    public function generateCollections(string $format): array
    {
        $doc = [
            'links' => [],
            'collections' => [],
//            'crs' => [
//                "type" => "name",
//                "properties" => [
//                    "name" => "urn:ogc:def:crs:EPSG::4326"
//                ]
//            ]
            'crs' => [
                "http://www.opengis.net/def/crs/EPSG/0/4326"
            ]
        ];

        foreach ($this->getCollectionsFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['COLLECTIONS'], array_merge($this->params, ['f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                $_format === $format ? 'self' : 'alternate',
                $_format, 'This document as ' . $_format
            );
        }
        $doc['collections'][] = $this->generateCollection($this->records->getSlug(), $this->records->getTitle(), $this->records->getAbstract(), $format);
        return $doc;
    }

    public function getCollectionsFormats(): array
    {
        return [self::FORMAT_JSON, self::FORMAT_YAML];
    }

    public function generateCollection(string $id, string $title, string $description, string $format): array
    {
        $doc = [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'links' => [],
            'crs' => [
                "http://www.opengis.net/def/crs/EPSG/0/4326"
            ]
        ];
        foreach ($this->getItemFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['ITEMS'], array_merge($this->params, ['collectionId' => $id, 'f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                'items',
                $_format,
                $id . ' items as ' . $_format
            );
        }
        foreach ($this->getCollectionIdxFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['COLLECTION_IDX'], array_merge($this->params, ['collectionId' => $id, 'f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                $_format === $format ? 'self' : 'alternate',
                $_format,
                'This document' . ($_format === $format ? '' : ' as ' . $_format)
            );
        }

        foreach ($this->getQueryablesFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['QUERYABLES'], array_merge($this->params, ['collectionId' => $id, 'f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                'queryables',
                $_format,
                'Queryables JSON schema as ' . $_format
            );
        }

        foreach ($this->getSortablesFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['SORTABLES'], array_merge($this->params, ['collectionId' => $id, 'f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                'sortables',
                $_format,
                'Sortables JSON schema as ' . $_format
            );
        }

        foreach ($this->getFunctionsFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['FUNCTIONS'], array_merge($this->params, ['collectionId' => $id, 'f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                'functions',
                $_format,
                'Functions as ' . $_format
            );
        }

        return $doc;
    }

    public function getCollectionIdxFormats(): array
    {
        return [self::FORMAT_JSON, self::FORMAT_YAML];
    }

    public function getItemFormats(): array
    {
        return [self::FORMAT_JSON, self::FORMAT_GEOJSON, self::FORMAT_YAML];
    }

    public function generateQueryables(array $doc, string $id): array
    {
        $doc['$id'] = $this->router->generate($this->ROUTES['QUERYABLES'], array_merge($this->params, ['collectionId' => $id]), UrlGeneratorInterface::ABSOLUTE_URL);

        return $doc;
    }

    public function getQueryablesFormats(): array
    {
        return [self::FORMAT_JSONSCHEMA, self::FORMAT_JSON, self::FORMAT_YAML];
    }

    public function generateSortables(array $doc, string $id): array
    {
        $doc['$id'] = $this->router->generate($this->ROUTES['SORTABLES'], array_merge($this->params, ['collectionId' => $id]), UrlGeneratorInterface::ABSOLUTE_URL);

        return $doc;
    }

    public function getSortablesFormats(): array
    {
        return [self::FORMAT_JSONSCHEMA, self::FORMAT_JSON, self::FORMAT_YAML];
    }
//
//    public function generateFunctions(array $doc): array
//    {
//        return $doc;
//    }

    public function getFunctionsFormats(): array
    {
        return [self::FORMAT_JSON, self::FORMAT_YAML];
    }

    public function generateFeatureCollection(Query $query, array $features, int $matched): array
    {
        $returned = count($features);
        $doc = [
            'type' => 'FeatureCollection',
            'links' => [],
            'timeStamp' => (new \DateTime('now'))->format('Y-m-d\TH:i:s.v'),
            'numberMatched' => $matched,
            'numberReturned' => $returned,
            'features' => $features,
        ];
        $allGet = $query->getAllGet();
        foreach ($this->getItemFormats() as $_format) {
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['ITEMS'], array_merge($this->params, ['collectionId' => $this->records->getSlug()], $allGet, ['f' => $_format]), UrlGeneratorInterface::ABSOLUTE_URL),
                $_format === $query->getFormat() ? 'self' : 'alternate',
                $_format,
                'This document' . ($_format === $query->getFormat() ? '' : ' as ' . $_format)
            );
        }
        $offset = $query->getOffset() - $query->getLimit();
        if ($offset >= 0) {
            $allGet['offset'] = $offset;
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['ITEMS'], array_merge($this->params, ['collectionId' => $this->records->getSlug()], $allGet), UrlGeneratorInterface::ABSOLUTE_URL),
                'prev',
                $query->getFormat(),
                'previous page'
            );
        }
        $offset = $query->getOffset() + $query->getLimit();
        if ($offset <= $matched) {
            $allGet['offset'] = $offset;
            $this->addLink(
                $doc,
                $this->router->generate($this->ROUTES['ITEMS'], array_merge($this->params, ['collectionId' => $this->records->getSlug()], $allGet), UrlGeneratorInterface::ABSOLUTE_URL),
                'next',
                $query->getFormat(),
                'next page'
            );
        }

        return $doc;
    }
}
