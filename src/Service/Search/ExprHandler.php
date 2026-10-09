<?php

namespace App\Service\Search;

abstract class ExprHandler
{
    protected $escapeChar = '\\';
    protected $singleChar = '_';
    protected $wildCard = '%';
    protected $spatialProperty = ['bboxw', 'bboxs', 'bboxe', 'bboxn'];
    protected $idx;

    public function __construct()
    {
        $this->idx = 0;
    }

    protected function valueForLike($escapeChar, $singleChar, $wildCard, $value): array|string|null
    {
        $value = preg_replace(self::getRegex($escapeChar, $wildCard), $this->wildCard, $value);
        $value = preg_replace(self::getRegex($escapeChar, $singleChar), $this->singleChar, $value);
        return preg_replace(self::getRegex($escapeChar, $escapeChar), $this->escapeChar, $value);
    }

    private static function getRegex($escape, $character): string
    {
        return '/(?<!'.self::escape($escape).')('.self::escape($character).')/';
    }

    private static function escape($character)
    {
        if (str_contains('!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~', $character)) {
            return '\\'.$character;
        }

        return $character;
    }

    protected function getName($name)
    {
        return $name;
    }

    protected function addParameter($property, $value, &$parameters): string
    {
        $key = str_replace('.', '_', $property) . '_' . ($this->idx++);

        $parameters[$key] = $value;

        return ':' . $key;
    }

    protected static function addToBbox($x, $y, array $bbox = null): ?array
    {
        if ($bbox === null) {
            return [$x, $y, $x, $y];
        }

        $bbox[0] = min($bbox[0], $x);
        $bbox[1] = min($bbox[1], $y);
        $bbox[2] = max($bbox[2], $x);
        $bbox[3] = max($bbox[3], $y);

        return $bbox;
    }

    protected static function bboxForGeoJson(array $geoFeature)
    {
        if (isset($geoFeature['bbox'])) {
            return $geoFeature['bbox'];
        } elseif (isset($geoFeature['geometry'])) {
            return self::createBbox($geoFeature['geometry']['coordinates']);
        } elseif (isset($geoFeature['coordinates'])) {
            return self::createBbox($geoFeature['coordinates']);
        }

        return null;
    }

    protected static function createBbox(array $coordinates): ?array
    {
        $bbox = null;

        if (!is_array($coordinates[0])) {
            return self::addToBbox($coordinates[0], $coordinates[1]);
        }

        if (!is_array($coordinates[0][0])) {
            foreach ($coordinates as $coord) {
                $bbox = self::addToBbox($coord[0], $coord[1], $bbox);
            }

            return $bbox;
        }

        return self::createBbox($coordinates[0]);
    }

    protected function jsonGeometryToWkt(array $geoFeature): string
    {
        $geom = $geoFeature['geometry'];
        switch ($geom["type"]) {
            case "Point":
                return "POINT(" . $geom["coordinates"][0] . " " . $geom["coordinates"][1] . ")";
            case "MultiPoint":
                $coords = $this->toStringCoords($geom["coordinates"]);
                return "MULTIPOINT(" . implode(",", $coords) . ")";
            case "LineString":
                $coords = $this->toStringCoords($geom["coordinates"]);
                return "LINESTRING(" . implode(",", $coords) . ")";
            case "Polygon":
                $rings = $this->toSetOfStringCoords($geom["coordinates"]);
                return "POLYGON(" . implode(",", $rings) . ")";
            case "MultiLineString":
                $lines = $this->toSetOfStringCoords($geom["coordinates"]);
                return "MULTILINESTRING(" . implode(",", $lines) . ")";
            case "MultiPolygon":
                $multi = [];
                foreach ($geom["coordinates"] as $poly) {
                    $rings = $this->toSetOfStringCoords($poly);
                    $multi[] = "(" . implode(",", $rings) . ")";
                }
                return "MULTIPOLYGON(" . implode(",", $multi) . ")";
        }
        throw new \Exception("not supported geometry type.");
    }

    private function toStringCoords(array $coordsArr): array
    {
        return array_map(function ($val) {
            return $val[0] . " " . $val[1];
        }, $coordsArr);
    }

    private function toSetOfStringCoords($list): array
    {
        $set = [];
        foreach ($list as $item) {
            $coords = $this->toStringCoords($item);
            $set[] = "(" . implode(",", $coords) . ")";
        }
        return $set;
    }
}
