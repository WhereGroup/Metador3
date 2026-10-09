<?php

namespace App\Service\Search;

interface ExprHandlerInterface
{
    public function andx(array $items);

    public function orx(array $items);

    public function not($item);

    public function in($propertyName, array $items, &$parameters);

    public function eq($propertyName, $value, &$parameters);

    public function neq($property, $value, &$parameters);

    public function like($propertyName, $value, &$parameters, $escapeChar, $singleChar, $wildCard);

    public function notLike($propertyName, $value, &$parameters, $escapeChar, $singleChar, $wildCard);

    public function between($propertyName, $lower, $upper, &$parameters);

    public function gt($propertyName, $value, &$parameters);

    public function gte($propertyName, $value, &$parameters);

    public function lt($property, $value, &$parameters);

    public function lte($propertyName, $value, &$parameters);

    public function isNull($property);

    public function isNotNull($propertyName);

    /**** geo spatial ****/

    public function bbox($propertyName, $geoFeature, &$parameters);

    public function intersects($propertyName, $geoFeature, &$parameters);

    public function contains($propertyName, $geoFeature, &$parameters);

    public function within($propertyName, $geoFeature, &$parameters);
}
