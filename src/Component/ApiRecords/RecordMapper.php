<?php

namespace App\Component\ApiRecords;

use App\Service\Search\JsonFilterReader;
use App\Service\Search\SearchInterface;
use App\Service\Search\SolrSearch;

class RecordMapper
{

    public function __construct(private SearchInterface $search)
    {
    }

    private function addProperty(array &$array, string $key, mixed $value = null): void
    {
        if ($value) {
            $array[$key] = $value;
        }
    }

    private function mapContact(array $objectContact): array
    {
        $contact = [];
        if (($id = $objectContact['id'] ?? null)) {
//            $this->search->setSpatialExpression(JsonFilterReader::read(["eq" => ["id" => $id]], $this->search->createExpression()));
//            $row = $this->search->find()['rows'][0] ?? null;
//            if ($row) {
//                $objectContact = array_merge($objectContact, $row);
//            }
            unset($objectContact['id']);
        }
        $this->addProperty($contact, 'id', $objectContact['id'] ?? null);
        $this->addProperty($contact, 'uuid', $objectContact['uuid'] ?? null);
        $this->addProperty($contact, 'name', $objectContact['individualName'] ?? null);
        $this->addProperty($contact, 'organization', $objectContact['organisationName'] ?? null);
        $this->addProperty(
            $contact,
            'positionName',
            isset($objectContact['positionName']) ?
                (is_array($objectContact['positionName']) ? $objectContact['positionName'] : [$objectContact['positionName']])
                : null
        );

//        $this->addProperty($contact, 'logo', $records->getPositionName());
        $phones = [];
        if (isset($objectContact['voice'])) {
            $phones[] = [
                'value' => $objectContact['voice'],
//                'roles' => ['work']
            ];
        }
        if (isset($objectContact['facsimile'])) {
            $phones[] = [
                'value' => $objectContact['facsimile'],
                'roles' => ['fax']
            ];
        }

        if (count($phones) > 0) {
            $this->addProperty($contact, 'phones', $phones);
        }

        if (isset($objectContact['role'])) {
            $this->addProperty($contact, 'roles', [$objectContact['role']]);
        }
        $this->addProperty($contact, 'email', $objectContact['email'] ?? null);
        $address = [];
        $this->addProperty($address, 'deliveryPoint', $objectContact['deliveryPoint'] ?? null);
        $this->addProperty($address, 'city', $objectContact['city'] ?? null);
        $this->addProperty($address, 'administrativeArea', $objectContact['administrativeArea'] ?? null);
        $this->addProperty($address, 'postalCode', $objectContact['postalCode'] ?? null);
        $this->addProperty($address, 'country', $objectContact['country'] ?? null);
        if (count($address) > 0) {
            $this->addProperty($address, 'addresses', [$address]);
        }
        $this->addProperty($address, 'hoursOfService', $objectContact['hoursOfService'] ?? null);
        if (isset($objectContact['url'])) {
            $link = [
                'href' => $objectContact['url'],
                'type' => 'text/html',
            ];
            $this->addProperty($address, 'title', $objectContact['urlDescription'] ?? null);
            $this->addProperty($address, 'links', [$link]);
//        } elseif (isset($objectContact['links'])) {
//            $this->addProperty($address, 'links', $objectContact['links']);
        }

        return $contact;
    }

    public function mapGeometry(array $object)
    {
        $poly = $object['polygon'];
        $list = explode(' ', $poly['posList']);
        $dim = (int)$poly['srsDimension'];
        $count = count($list);
        if ($count % $dim !== 0) {
            throw new OgcApiException(OgcApiException::NOAPPLICABLECODE, 'Geometry is not valid');
        }
        for ($i = 0; $i < $dim; $i++) {
            if ($list[$i] !== $list[$count - $dim + $i]) {
                throw new OgcApiException(OgcApiException::NOAPPLICABLECODE, 'Geometry is not valid');
            }
        }
        $coordinates = [];
        for ($i = $dim - 1; $i < $count; $i = $i + $dim) {
            $coord = [];
            for ($j = $dim - 1; $j >= 0; $j--) {
                $coord[] = (float)$list[$i - $j];
            }
            $coordinates[] = $coord;
        }
        return [$coordinates];
    }


    public function mapProperties(array $object)
    {

        $object['description'] = $object['abstract'];
        $object['created'] = $object['_insert_time']; //    format: date-time
        $object['updated'] = $object['_date']; //    format: date-time

//            'type' => $object['_type'],
//  type:
//    type: string
//    description: |-
//    The nature or genre of the resource. The value
//      should be a code, convenient for filtering
//                                       records. Where available, a link to the canonical
//      URI of the record type resource will be added to
//      the 'links' property.

//    keywords:
//    type: array
//        description: |-
//    The topic or topics of the resource. Typically
//      represented using free-form keywords, tags, key
//      phrases, or classification codes.
//    items:
//      type: string
//  themes:
//    type: array
//        description: |-
//    A knowledge organization system used to classify
//      the resource.
//    minItems: 1
//    items:
//      $ref: 'theme.yaml'
        $object['language'] = ['code' => $object['language']];
        $object['languages'] = [['code' => $object['language']]];
//  resourceLanguages:
//    type: array
//        description: |-
//    The list of languages in which the resource
//      described by this record is available.
//    items:
//      $ref: 'language.yaml'
//  externalIds:
//    type: array
//        description: |-
//    An identifier for the resource assigned by an
//      external (to the catalog) entity.
//    items:
//      type: object
//      properties:
//        scheme:
//          type: string
//          description: |-
//    A reference to an authority or identifier
//            for a knowledge organization system from
//            which the external identifier was obtained.
//    It is recommended that the identifier be a
//            resolvable URI.
//    value:
//          type: string
//          description: The value of the identifier.
//    required:
//        - value
//  formats:
//    type: array
//        description: |-
//    A list of available distributions of the resource.
//    items:
//      $ref: 'format.yaml'
//  contacts:
//    type: array
//        description: |-
//    A list of contacts qualified by their role(s) in
//      association to the record or the resource described
//      by the record.
//    items:
//      $ref: 'contact.yaml'
//  license:
//    $ref: 'license.yaml'
//  rights:
//    type: string
//    description: |-
//    A statement that concerns all rights not addressed
//      by the license such as a copyright statement.
        foreach ($object as $key => $value) {
            if (str_starts_with($key, '_')) {
                unset($object[$key]);
            }
        }
        if (isset($object['polygon'])) {
            unset($object['polygon']);
        }

        if (isset($object['distributionContact'])) {
            $contact = [];
            foreach ($object['distributionContact'] as $value) {
                $contact[] = $this->mapContact($value);
            }
            $this->addProperty($object, 'distributionContact', $contact);
        }

        if (isset($object['responsibleParty'])) {
            $contact = [];
            foreach ($object['responsibleParty'] as $value) {
                $contact[] = $this->mapContact($value);
            }
            $this->addProperty($object, 'responsibleParty', $contact);
        }

        if (isset($object['contact'])) {
            $contact = [];
            foreach ($object['contact'] as $value) {
                $contact[] = $this->mapContact($value);
            }
            $this->addProperty($object, 'contact', $contact);
        }

        if (isset($object['bbox'])) {
            // todo extentTypeCode
            $this->addProperty(
                $object,
                'bbox',
                [
                    (float)$object['bbox']['wLongitude'],
                    (float)$object['bbox']['sLatitude'],
                    (float)$object['bbox']['eLongitude'],
                    (float)$object['bbox']['nLatitude']
                ]
            );
        }

        return $object;
    }

    public function mapRow($row): array
    {
        $row = (array)$row;
        $object = $row['object'];
        $mapped = $this->mapProperties($object);
        $meta = [
            'id' => $object['_uuid'],
            'type' => 'Feature',
            'properties' => $mapped,
//            'time' => '', # optional
//            'conformsTo' => '', # optional
//            'links' => [], # optional
//            'linkTemplates' => [], # optional
//            'all' => array_merge($object)
        ];
        $coordinates = isset($object['polygon']) ? $this->mapGeometry($object) : ($mapped['bbox'] ? $this->boxToPolygonCoordinates($mapped['bbox']) : null);
        if ($coordinates) {
            $meta['geometry'] = [
                'type' => 'Polygon',
                'coordinates' => $coordinates
            ];
        }
        return $meta;
    }

    public function mapRows(array $rows): array
    {
        $meta = [];
        foreach ($rows as $row) {
            $meta[] = $this->mapRow($row);
        }
        return $meta;
    }

    private function boxToPolygonCoordinates(array $bbox): array
    {
        return [
            [
                [$bbox[0], $bbox[1]],
                [$bbox[2], $bbox[1]],
                [$bbox[2], $bbox[3]],
                [$bbox[0], $bbox[3]],
                [$bbox[0], $bbox[1]],
            ]
        ];
    }
}
