<?php

namespace App\Component\ApiRecords;


use App\Service\Search\Expression;
use App\Service\Search\ExprHandler;
use App\Service\Search\FilterReader;

class OgcApiCqlJsonFilterReader implements FilterReader
{

    /**
     * @param array $expression
     * @param string $orOrAndoperator
     * @param array $filter
     * @return array
     * @throws OgcApiException
     */
    public static function addToFilter(array $expression, string $orOrAndoperator, array $filter = [])
    {
        if ([] === $filter) {
            return $expression;
        } elseif (isset($filter['op']) && ($filter['op'] === $orOrAndoperator)) {
            $filter['args'][] = $expression;
            return $filter;
        } elseif (!(isset($filter['op']) && ($filter['op'] === 'and') || $filter['op'] === 'or')
            || (isset($filter['op']) && ($filter['op'] !== $orOrAndoperator))) {
            return [
                'op' => $orOrAndoperator,
                'args' => [
                    $filter,
                    $expression
                ]
            ];
        }
        throw new OgcApiException(OgcApiException::BADREQUEST, 'Filter not applicable');
    }

    /**
     * @param $exprs
     * @param ExprHandler $expressionBuilder
     * @param array|null $map
     * @return Expression
     * @throws OgcApiException
     */
    public static function read($exprs, ExprHandler $expressionBuilder, array $map = null)
    {
        $fr = new OgcApiCqlJsonFilterReader($expressionBuilder, $map);
        $expr = $fr->readFun($exprs);
        return new Expression($expr, $fr->parameters);
    }

    protected $expressionBuilder;
    protected $parameters;
    protected $map;

    private function __construct(ExprHandler $expressionBuilder, ?array $map = null)
    {
        $this->expressionBuilder = $expressionBuilder;
        $this->parameters = [];
        $this->map = $map;
    }

    /**
     * @param array $filter
     * @return array
     */
    private function readFunArgs(array $filter)
    {
        $items = [];
        foreach ($filter as $idx => $value) {
            $items[] = self::readArg($value);
        }
        return $items;
    }

    private function readArg(array $filter)
    {
        if (isset($filter['op'])) {
            return self::readFun($filter);
        } else {
            return $filter;
        }
    }

    private function getProperty(array $args): string
    {
        $props = [];
        foreach ($args as $value) {
            if (isset($value['property'])) {
                $props[] = $value['property'];
            }
        }
        if (count($props) !== 1) {
            throw new OgcApiException(OgcApiException::BADREQUEST, 'Filter: the Property is not applicable.');
        }

        $name = $props[0];

        if ($this->map === null) {
            return $name;
        }

        if (isset($this->map[strtolower($name)])) {
            return $this->map[strtolower($name)];
        }

        throw new OgcApiException(OgcApiException::BADREQUEST, 'Filter: the Property is not applicable.');
    }

    private function getNotProperties(array $args, int $minCount): array
    {
        $props = [];
        foreach ($args as $value) {
            if (isset($value['property'])) {
                continue;
            }
            if (isset($value['op'])) {
                $props[] = $this->readFun($value);
            } else {
                $props[] = $value;
            }
        }
        if (count($props) < $minCount) {
            throw new OgcApiException(OgcApiException::BADREQUEST, 'Filter: The Arguments sind not applicable');
        }
        return $props;
    }

    private function readFun(array $filter)
    {
        $op = $filter['op'];
        $args = $filter['args'];

        switch ($op) {
            case 'and':
                $list = $this->readFunArgs($args);
                if (count($list) > 1) {
                    return $this->expressionBuilder->andx($list);
                } elseif (count($list) === 1) {
                    return $list[0];
                }
                throw new OgcApiException(OgcApiException::BADREQUEST, 'Filter: "and" is not applicable.');
            case 'or':
                $list = self::readFunArgs($args);
                if (count($list) > 1) {
                    return $this->expressionBuilder->orx($list);
                } elseif (count($list) === 1) {
                    return $list[0];
                }
                throw new OgcApiException(OgcApiException::BADREQUEST, 'Filter "or" is not applicable.');
            case 'not':
                $subFun = (isset($args[0]['op'])) ? $args[0]['op'] : null;
                if ($subFun === 'like') {
                    $nl = array_merge($args[0]);
                    $nl['op'] = 'notlike';
                    return $this->readFun($nl);
                } elseif ($subFun === '=' || $subFun === 'eq') {
                    $nl = array_merge($args[0]);
                    $nl['op'] = 'neq';
                    return $this->readFun($nl);
//                } elseif ($subFun === '=' || $subFun === 'in') {
//                    $nl = array_merge($args[0]);
//                    $nl['op'] = 'notin';
//                    return $this->readFun($nl);
                } else {
                    $item = $this->readFunArgs($args[0]);
                    return $this->expressionBuilder->not($item);
                }
            case 'isNull':
                return $this->expressionBuilder->isNull($this->getProperty($args));
            case 'eq':
            case '=':
                if (null === $args[0] || null === $args[1]) {
                    return $this->expressionBuilder->isNull($this->getProperty($args));
                } else {
                    return $this->expressionBuilder->eq($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
                }
            case 'neq':
            case '<>':
                if (null === $args[0] || null === $args[1]) {
                    return $this->expressionBuilder->isNotNull($this->getProperty($args));
                } else {
                    return $this->expressionBuilder->neq($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
                }
            case 'like':
                $fargs =  $this->getNotProperties($args, 1);
                return $this->expressionBuilder->like(
                    $this->getProperty($args),
                    $fargs[0],
                    $this->parameters,
                    isset($fargs[1]) && $fargs[1] ? $fargs[1] : '\\',
                    isset($fargs[2]) && $fargs[2] ? $fargs[2] : '_',
                    isset($fargs[3]) && $fargs[3] ? $fargs[3] : '%'
                );
            case 'notlike':
                $fargs =  $this->getNotProperties($args, 1);
                return $this->expressionBuilder->notLike(
                    $this->getProperty($args),
                    $fargs[0],
                    $this->parameters,
                    isset($fargs[1]) && $fargs[1] ? $fargs[1] : '\\',
                    isset($fargs[2]) && $fargs[2] ? $fargs[2] : '_',
                    isset($fargs[3]) && $fargs[3] ? $fargs[3] : '%'
                );
            case 'in':
                return $this->expressionBuilder->in($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
            case 'between':
                $bargs = $this->getNotProperties($args, 2);
                return $this->expressionBuilder->between($this->getProperty($args), $bargs[0], $bargs[1], $this->parameters);
            case 'gt':
            case '>':
                return $this->expressionBuilder->gt($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
            case 'gte':
            case '>=':
                return $this->expressionBuilder->gte($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
            case 'lt':
            case '<':
                return $this->expressionBuilder->lt($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
            case 'lte':
            case '<=':
                return $this->expressionBuilder->lte($this->getProperty($args), $this->getNotProperties($args, 1)[0], $this->parameters);
            case 's_intersects':
                return $this->expressionBuilder->intersects('geom', $this->bboxToPolygon($this->getNotProperties($args, 1)[0]), $this->parameters);
            case 's_contains':
                return $this->expressionBuilder->contains('geom', $this->bboxToPolygon($this->getNotProperties($args, 1)[0]), $this->parameters);
            case 's_within':
                return $this->expressionBuilder->within('geom', $this->bboxToPolygon($this->getNotProperties($args, 1)[0]), $this->parameters);
            default:
                throw new OgcApiException(OgcApiException::NOAPPLICABLECODE);
        }
    }

    private function bboxToPolygon(array $geom): array
    {
        if (isset($geom['bbox'])) {
            return $this->geoJsonFromBbox($geom['bbox']);
        }
        return $geom;
    }


    /**
     * @param array $bbox
     * @return array
     */
    private function geoJsonFromBbox(array $bbox)
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [$bbox[0], $bbox[1]],
                        [$bbox[2], $bbox[1]],
                        [$bbox[2], $bbox[3]],
                        [$bbox[0], $bbox[3]],
                        [$bbox[0], $bbox[1]],
                    ]
                ],
            ]
        ];
    }
}
