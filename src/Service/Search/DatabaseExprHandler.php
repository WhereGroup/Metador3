<?php

namespace App\Service\Search;

use Doctrine\ORM\Query\Expr;

class DatabaseExprHandler extends ExprHandler implements ExprHandlerInterface
{
    public function andx(array $items): Expr\Andx
    {
        return new Expr\Andx($items);
    }

    public function orx(array $items): Expr\Orx
    {
        return new Expr\Orx($items);
    }

    public function not($item)
    {
        $expr = new Expr();

        return $expr->not($item);
    }

    public function in($property, array $items, &$parameters)
    {
        $expr = new Expr();
        $placeHolders = [];
        foreach ($items as $item) {
            $placeHolders[] = self::addParameter($property, $item, $parameters);
        }

        return $expr->in($this->getName($property), implode(",", $placeHolders));
    }

    public function eq($property, $value, &$parameters)
    {
        $expr = new Expr();

        return $expr->eq($this->getName($property), self::addParameter($property, $value, $parameters));
    }

    public function neq($property, $value, &$parameters)
    {
        $expr = new Expr();

        return $expr->neq($this->getName($property), self::addParameter($property, $value, $parameters));
    }

    protected function getName($columnName)
    {
        if ($columnName === 'role') {
            return 'g.role';
        }

        return 'm.' . $columnName;
    }

    public function like($property, $value, &$parameters, $escapeChar = '\\', $singleChar = '_', $wildCard = '%')
    {
        $expr = new Expr();

        return $expr->like(
            $this->getName($property),
            self::addParameter(
                $property,
                $this->valueForLike($escapeChar, $singleChar, $wildCard, $value),
                $parameters
            )
        );
    }

    public function notLike($property, $value, &$parameters, $escapeChar = '\\', $singleChar = '_', $wildCard = '%')
    {
        $expr = new Expr();

        return $expr->notLike(
            $this->getName($property),
            $this->valueForLike($escapeChar, $singleChar, $wildCard, $value)
        );
    }

    public function between($property, $lower, $upper, &$parameters)
    {
        $expr = new Expr();

        return $expr->between(
            $this->getName($property),
            self::addParameter($property, $lower, $parameters),
            self::addParameter($property, $upper, $parameters)
        );
    }

    public function gt($property, $value, &$parameters)
    {
        $expr = new Expr();

        return $expr->gt($this->getName($property), self::addParameter($property, $value, $parameters));
    }

    public function gte($property, $value, &$parameters)
    {
        $expr = new Expr();

        return $expr->gte($this->getName($property), self::addParameter($property, $value, $parameters));
    }

    public function lt($property, $value, &$parameters)
    {
        $expr = new Expr();

        return $expr->lt($this->getName($property), self::addParameter($property, $value, $parameters));
    }

    public function lte($property, $value, &$parameters)
    {
        $expr = new Expr();

        return $expr->lte($this->getName($property), self::addParameter($property, $value, $parameters));
    }

    public function isNull($property)
    {
        $expr = new Expr();

        return $expr->isNull($this->getName($property));
    }

    public function isNotNull($property)
    {
        $expr = new Expr();

        return $expr->isNotNull($this->getName($property));
    }

    public function bbox($propertyName, $geoFeature, &$parameters): Expr\Andx
    {
        if (is_array($this->spatialProperty)) {
            // check if $geoFeature is an [w,s,e,n] or GeoJSON "Feature" / GeoJSON "geometry"
            $bbox = self::bboxForGeoJson($geoFeature);

            if (count($geoFeature) === 4 && !isset($geoFeature['geometry'])) {
                $bbox = $geoFeature;
            }

            // no spatial column
            // "spatially intersect" - (share any portion of space)
            return new Expr\Andx(
                [
                    $this->lt($this->spatialProperty[0], floatval($bbox[2]), $parameters),
                    $this->gt($this->spatialProperty[2], floatval($bbox[0]), $parameters),
                    $this->lt($this->spatialProperty[1], floatval($bbox[3]), $parameters),
                    $this->gt($this->spatialProperty[3], floatval($bbox[1]), $parameters),
                ]
            );
        }

        throw new \Exception('Operation "bbox" for a spatial database is not yet implemented');
    }

    public function contains($propertyName, $geoFeature, &$parameters): Expr\Andx
    {
        /**
         * ST_Contains — Returns true if and only if no points of B lie in the exterior of A, and at least one point
         * of the interior of B lies in the interior of A.
         * boolean ST_Contains(geometry geomA, geometry geomB)
         */
        if (is_array($this->spatialProperty)) {
            $bbox = self::bboxForGeoJson($geoFeature);

            return new Expr\Andx(
                [
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[0]),
                        '<=',
                        self::addParameter($this->spatialProperty[0], floatval($bbox[0]), $parameters)
                    ),
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[2]),
                        '>=',
                        self::addParameter($this->spatialProperty[2], floatval($bbox[2]), $parameters)
                    ),
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[1]),
                        '<=',
                        self::addParameter($this->spatialProperty[1], floatval($bbox[1]), $parameters)
                    ),
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[3]),
                        '>=',
                        self::addParameter($this->spatialProperty[3], floatval($bbox[3]), $parameters)
                    ),
                ]
            );
        }

        throw new \Exception('Operation "contains" for a spatial database is not yet implemented');
    }

    public function within($propertyName, $geoFeature, &$parameters): Expr\Andx
    {
        /**
         * ST_Within — Returns true if the geometry A is completely inside geometry B
         * boolean ST_Within(geometry A, geometry B);
         */
        if (is_array($this->spatialProperty)) {
            /* no geometry property -> 4 bbox values w,s,e,n */
            $bbox = self::bboxForGeoJson($geoFeature);

            return new Expr\Andx(
                [
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[0]),
                        '>',
                        self::addParameter($this->spatialProperty[0], floatval($bbox[0]), $parameters)
                    ),
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[2]),
                        '<',
                        self::addParameter($this->spatialProperty[2], floatval($bbox[2]), $parameters)
                    ),
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[1]),
                        '>',
                        self::addParameter($this->spatialProperty[1], floatval($bbox[1]), $parameters)
                    ),
                    new Expr\Comparison(
                        $this->getName($this->spatialProperty[3]),
                        '<',
                        self::addParameter($this->spatialProperty[3], floatval($bbox[3]), $parameters)
                    ),
                ]
            );
        }

        throw new \Exception('Operation "within" for a spatial database is not yet implemented');
    }

    public function intersects($propertyName, $geoFeature, &$parameters): Expr\Andx
    {
        // "spatially intersect" - (share any portion of space)
        if (is_array($this->spatialProperty)) {
            // no spatial column -> bbox 4 values
            return $this->bbox($propertyName, self::bboxForGeoJson($geoFeature), $parameters);
        }

        throw new \Exception('Operation "intersects" for a spatial database is not yet implemented');
    }
}
