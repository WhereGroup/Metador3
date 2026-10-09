<?php

namespace App\Component\ApiRecords;

use App\Entity\Records;
use Symfony\Component\HttpFoundation\Request;

class Query
{
    private $records;
    private $request;
    private $format;
    private $queryables;
    private $queryablesMap;
    private $sortables;
    private $sortablesMap;
    private $functions;
    private $offset = 0;
    private $limit = 10;
    private $filter = null;
    private $f;
    private $q = '';
    private $sortBy = [];
    private $allGet = [];

    public function __construct(Records $records, Request $request, string $format, array $queryables, array $queryablesMap, array $sortables, array $sortablesMap, array $functions)
    {
        $this->records = $records;
        $this->request = $request;
        $this->format = $format;
        $this->queryables = $queryables;
        $this->sortables = $sortables;
        $this->queryablesMap = $queryablesMap;
        $this->sortablesMap = $sortablesMap;
        $this->functions = $functions;
        $this->allGet = $this->request->query->all();
        $this->parseGet(array_merge($this->allGet));
    }

    public function getFormat(): string
    {
        return $this->format;
    }


    public function getAllGet(): array
    {
        return $this->allGet;
    }
//
//    public function getQueryables(): array
//    {
//        return $this->queryables;
//    }
//
//    public function getSortables(): array
//    {
//        return $this->sortables;
//    }

    public function getQueryablesMap(): array
    {
        return $this->queryablesMap;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return null
     */
    public function getFilter()
    {
        return $this->filter;
    }
//
//    public function getF(): string
//    {
//        return $this->f;
//    }

    public function getQ(): string
    {
        return $this->q;
    }

    public function getSortBy(): array
    {
        return $this->sortBy;
    }

    public function getQuery(): array
    {
        return [
            'offset' => $this->offset,
            'limit' => $this->limit,
            'filter' => $this->filter,
            'q' => $this->q,
            'sortby' => $this->sortBy,
        ];
    }

    private function get(array &$params, string $key, $default = null, bool $remove = true)
    {
        $val = isset($params[$key]) ? $params[$key] : $default;
        if ($remove && isset($params[$key])) {
            unset($params[$key]);
        }
        return $val;
    }

    private function parseGet(array $all): void
    {
        $this->f = $this->get($all, "f");
        $this->offset = (int)($this->get($all, 'offset') ?? 0);
        if (($limit = $this->get($all, 'limit'))) {
            $limit = (int)$limit;
            if ($limit > 0 && $limit < 10000) {
                $this->limit = $limit;
            } else {
                throw new OgcApiException(OgcApiException::BADREQUEST, 'The limit parameter must be between 1 and 10000');
            }
        } else {
            $this->limit = 10;
        }
        $this->sortBy = $this->parseSortby($this->get($all, 'sortby', ''));

        $this->q = implode(' ', $this->parseCsvs($this->get($all, 'q', '')));

        if (($filter = $this->get($all, 'filter'))) {
            $this->filter = $this->get($all, 'filter-lang', 'cql-text') === 'cql-text'
                ? $this->parseFilterCqlText($filter)
                : $this->parseFilterCqlJson($filter);
        }

        if (($ids = $this->get($all, 'ids', ''))) {
            $expression = [
                'op' => 'in',
                'args' => [
                    ['property' => 'id'],
                    $this->parseCsvs($ids)
                ],
            ];
            $this->filter = OgcApiCqlJsonFilterReader::addToFilter($expression, 'and', $this->filter ?? []);
        }

        if (($bbox = $this->get($all, 'bbox'))) {
            $expression = [
                'op' => 's_intersects',
                'args' => [
                    ['property' => 'geometry'],
                    ['bbox' => $this->parseBbox($bbox)]
                ]
            ];
            $this->filter = OgcApiCqlJsonFilterReader::addToFilter($expression, 'and', $this->filter ?? []);
        }

        if (($datetime = $this->get($all, 'datetime'))) {
            $dates = explode('/', $datetime);
            if (!count($dates) === 2) {
                throw new OgcApiException(OgcApiException::BADREQUEST, 'The parameter has no valid value');
            }
            if ($dates[0] === '..' || $dates[0] === '') {
                $this->filter = OgcApiCqlJsonFilterReader::addToFilter(
                    [
                        'op' => '<=',
                        'args' => [
                            ['property' => 'datetime'],
                            $dates[1]
                        ]
                    ], 'and', $this->filter ?? []);
            } elseif ($dates[1] === '..' || $dates[1] === '') {
                $this->filter = OgcApiCqlJsonFilterReader::addToFilter(
                    [
                        'op' => '>=',
                        'args' => [
                            ['property' => 'datetime'],
                            $dates[0]
                        ]
                    ], 'and', $this->filter ?? []);
            } else {
                $this->filter = OgcApiCqlJsonFilterReader::addToFilter(
                    [
                        'op' => 'between',
                        'args' => [
                            ['property' => 'datetime'],
                            $dates[0],
                            $dates[1]
                        ]
                    ], 'and', $this->filter ?? []);
            }
        }

        foreach ($all as $field => $value) {
            $validated = $this->getValid($this->get($all, $field), $field);
            $expression = [];
            if (is_array($validated)) {
                if (count($validated) === 0) {
                    throw new OgcApiException(OgcApiException::BADREQUEST, 'The parameter has no valid value');
                }
                $result = [];
                foreach ($validated as $v) {
                    $result[] = [
                        'op' => '=',
                        'args' => [
                            ['property' => $field],
                            $v
                        ]
                    ];
                }
                if (count($result) === 1) {
                    $expression = $result[0];
                } else {
                    $expression = [
                        'op' => 'or',
                        'args' => $result
                    ];
                }

            } else {
                $expression = [
                    'op' => '=',
                    'args' => [
                        ['property' => $field],
                        $validated
                    ],
                ];
            }
            $this->filter = OgcApiCqlJsonFilterReader::addToFilter($expression, 'and', $this->filter ?? []);
        }
    }

    public function parseFilterCqlJson(string $val): array
    {
        return json_decode($val, true);
    }

    public function parseFilterCqlText(string $val): array
    {
        // todo create parser and transform to cql-json
        return Cql2TextToJsonFilerConverter::read($val, $this->queryables, $this->functions);
    }

    private function parseCsvs(string $val): array
    {
        return $val ? preg_split('#\s?,\s?#', $val) : [];
    }

    private function parseSortby(string $val): array
    {
        $sortBy = []; // s. defaultSortOrder
        if ($val === '') {
            return $sortBy;
        }
        $items = preg_split('#\s?,\s?#', $val);
        foreach ($items as $item) {
            if (str_starts_with($item, '-')) {
                $sortBy[] = [
                    'field' => $this->getSortablePropertyName(substr($item, 1)),
                    'direction' => 'desc'
                ];
            } else {
                $sortBy[] = [
                    'field' => $this->getSortablePropertyName(str_starts_with($item, '+') ? substr($item, 1) : $item),
                    'direction' => 'asc'
                ];
            }
        }
        return $sortBy;
    }

    private function parseBbox(string $val): array
    {
        $bbox = preg_split('#\s?,\s?#', $val);
        if (count($bbox) !== 4) {
            throw new OgcApiException(OgcApiException::BADREQUEST);
        }
        return array_map(function ($item) {
            return (float)$item;
        }, $bbox);
    }

    private function getValid($value, $key = null)
    {
        if ($key === null) {
            return self::validateSimple($this->queryables, $value);
        }
        if (isset($this->queryables['properties'][$key])) {
            return self::validateSimple($this->queryables['properties'][$key], $value);
        }

        throw new OgcApiException(OgcApiException::BADREQUEST, 'The parameter "' . $key . '" is not in schema properties');
    }

    public static function validateSimple(array $schema, $value)
    {
        if ($schema['type'] === 'number') {
            return $schema['multipleOf'] === 10 ? (int)$value : (float)$value;
        } elseif ($schema['type'] === 'string') {
            return (string)$value;
        } elseif ($schema['type'] === ' boolean') {
            return (bool)$value;
        } elseif ($schema['type'] === 'null') {
            return null;
        } elseif ($schema['type'] === 'array') {
            if (is_string($value)) {
                $value = preg_split('#\s?,\s?#', $value);
            }
            $res = [];
            $num = 0;
            foreach ($value as $v) {
                $s = isset($schema['prefixItems']) ? $schema['prefixItems'][$num] : (isset($schema['items']) && $schema['items'] ? $schema['items'] : null);
                if ($s) {
                    $res[] = Query::validateSimple($s, $v);
                } else {
                    $res[] = $v;
                }
                $num++;
            }
            return $res;
        }
        throw new OgcApiException(OgcApiException::BADREQUEST, 'The value is not valid');
    }

    private function getSortablePropertyName(string $key): string
    {
        if (isset($this->sortables['properties'][$key]) && isset($this->sortablesMap[$key])) {
            return $this->sortablesMap[$key];
        }
        throw new OgcApiException(OgcApiException::BADREQUEST, 'No sortable property found');
    }
}
