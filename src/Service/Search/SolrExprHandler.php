<?php

namespace App\Service\Search;

class SolrExprHandler extends ExprHandler implements ExprHandlerInterface
{
    protected $singleChar = '?';
    protected $wildCard = '*';

    public function andx(array $items): string
    {
        $string = "";

        foreach ($items as $item) {
            $string .= " AND " . $item;
        }

        return ('(' . trim(substr($string, 5)) . ')');
    }

    public function orx(array $items): string
    {
        $string = "";

        foreach ($items as $item) {
            $string .= " OR " . $item;
        }

        return ('(' . trim(substr($string, 4)) . ')');
    }

    public function not($item): string
    {
        return '-(' . $item.')';
    }

    public function in($propertyName, array $items, &$parameters): string
    {
        $string = "";

        foreach ($items as $item) {
            $string .= " OR " . $item;
        }

        return $propertyName . ': (' . trim(substr($string, 4)) . ')';
    }

    public function eq($propertyName, $value, &$parameters): string
    {
        return ($propertyName . ':' . $value);
    }

    public function neq($property, $value, &$parameters): string
    {
        return ('-' . $property . ':' . $value);
    }

    public function like($propertyName, $value, &$parameters, $escapeChar = '\\', $singleChar = '_', $wildCard = '%'): string
    {
        return ($propertyName . ':' . str_replace($wildCard, $this->wildCard, $value));
    }

    public function notLike($propertyName, $value, &$parameters, $escapeChar = '\\', $singleChar = '_', $wildCard = '%'): string
    {
        return ('-' . $propertyName . ':' . str_replace($wildCard, $this->wildCard, $value));
    }

    public function between($propertyName, $lower, $upper, &$parameters): string
    {
        return ($propertyName . ':[' . $lower . ' TO ' . $upper . ']');
    }

    public function gt($propertyName, $value, &$parameters)
    {
        return ($propertyName . ':{' . $value . ' TO *}');
    }

    public function gte($propertyName, $value, &$parameters)
    {
        return ($propertyName . ':[' . $value . ' TO *]');
    }

    public function lt($property, $value, &$parameters)
    {
        return ($property . ':{* TO ' . $value . '}');
    }

    public function lte($propertyName, $value, &$parameters): string
    {
        return ($propertyName . ':[* TO ' . $value . ']');
    }

    public function isNull($property): string
    {
        return ('-' . $property . ':["" TO *]');
    }

    public function isNotNull($propertyName): string
    {
        return ($propertyName . ':["" TO *]');
    }

    public function bbox($propertyName, $geoFeature, &$parameters): string
    {
        if ($propertyName) {
            return $this->intersectsForGeom($propertyName, $geoFeature, $parameters);
        } else {
            return $this->withinForGeom($propertyName, $geoFeature, $parameters);
        }
    }

    public function bboxForBbox($propertyName, $geoFeature, &$parameters): string
    {
        if (is_array($this->spatialProperty)) {
            if (count($geoFeature) === 4 && !isset($geoFeature['geometry'])) {
                $bbox = $geoFeature;
            } else {
                $bbox = self::bboxForGeoJson($geoFeature);
            }

            return $this->andx([
                $this->lt($this->spatialProperty[0], floatval($bbox[2]), $parameters),
                $this->gt($this->spatialProperty[2], floatval($bbox[0]), $parameters),
                $this->lt($this->spatialProperty[1], floatval($bbox[3]), $parameters),
                $this->gt($this->spatialProperty[3], floatval($bbox[1]), $parameters),
            ]);
        }

        throw new \Exception('Operation "bbox" for a spatial database is not yet implemented');
    }

    public function within($propertyName, $geoFeature, &$parameters): string
    {
        if ($propertyName) {
            return "{!field f=" . $propertyName . "}IsWithin(" . $this->jsonGeometryToWkt($geoFeature) . ")";
        }
        throw new \Exception('Operation "within" for a spatial database is not yet implemented');
    }

    public function intersects($propertyName, $geoFeature, &$parameters): string
    {
        if ($propertyName) {
            return "{!field f=" . $propertyName . "}Intersects(" . $this->jsonGeometryToWkt($geoFeature) . ")";
        }
        throw new \Exception('Operation "intersects" for a spatial database is not yet implemented');
    }

    public function contains($propertyName, $geoFeature, &$parameters): string
    {
        if ($propertyName) {
            return "{!field f=" . $propertyName . "}Contains(" . $this->jsonGeometryToWkt($geoFeature) . ")";
        }
        throw new \Exception('Operation "contains" for a spatial database is not yet implemented');
    }
}
