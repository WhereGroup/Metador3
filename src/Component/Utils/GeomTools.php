<?php

namespace App\Component\Utils;

class GeomTools
{

    public static function polygonWktFromBbox(float $w, float $s, float $e, float $n)
    {
        try {
            $bboxPoly = [$w, $s, $e, $s, $e, $n, $w, $n, $w, $s];
            return self::polygonToWkt(implode(" ", $bboxPoly));
        } catch (\Throwable $e) {

        }
        return false;
    }

    public static function isLatLonPolygon(array $coordinates)
    {
        $count = count($coordinates);
        if ($count % 2 !== 0 || $count < 8
            || (int)$coordinates[0] == 1 || (int)$coordinates[0] == 2
            || (float)$coordinates[0] !== (float)$coordinates[$count - 2]
            || (float)$coordinates[1] !== (float)$coordinates[$count - 1]) {
            return false;
        }
        $i = 1;
        for (; $i < $count; $i = $i + 2) {
            if (!self::isLatLon($coordinates[$i - 1], $coordinates[$i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $polygon
     * @return bool|string
     */
    public static function polygonToWkt($polygon)
    {
        $coordinates = explode(' ', trim($polygon));

        if (!self::isLatLonPolygon($coordinates)) {
            return false;
        }

        return self::convertCoordinatesToWkt(self::swapAndCutCoordinates($coordinates));
    }

    public static function swapAndCutCoordinates(array $coordinates)
    {
        if (!self::coordinatesAreValid($coordinates)) {
            return false;
        }

        $string = "";
        $swap = (substr($coordinates[0], 1, 1) !== '.' || substr($coordinates[1], 2, 1) !== '.');

        $l = count($coordinates);
        for ($i = 0; $i < $l; $i += 2) {
            $x = self::cutCoordinatesLength($coordinates[$i]);
            $y = self::cutCoordinatesLength($coordinates[$i + 1]);

            $string .= $swap ? ' ' . $y . ' ' . $x : ' ' . $x . ' ' . $y;
        }

        return $coordinates = explode(' ', trim($string));
    }

    public static function coordinatesAreValid(array $coordinates)
    {
        // @codingStandardsIgnoreStart
        if (count($coordinates) < 2 || count($coordinates) % 2 !== 0 || (int)$coordinates[0] == 1 || (int)$coordinates[0] == 2) {
            // @codingStandardsIgnoreEnd
            return false;
        }

        return true;
    }

    private static function cutCoordinatesLength(string $coordinate, $maxLength = 7)
    {
        return (substr($coordinate, 1, 1) === '.')
            ? substr($coordinate, 0, $maxLength + 2)
            : substr($coordinate, 0, $maxLength + 3);
    }

    private static function convertCoordinatesToWkt(array $coordinates)
    {
        $string = "";
        $count = 0;

        foreach ($coordinates as $item) {
            $string .= $item . ((++$count % 2 === 0) ? ',' : ' ');
        }

        return 'POLYGON((' . trim($string, ', ') . '))';
    }

    public static function isLatLon($lon, $lat)
    {
        if ((float)$lat > 90 || (float)$lat < -90
            || (float)$lon > 180 || (float)$lon < -180) {
            return false;
        }
        return true;
    }
}