<?php

namespace App\Component\Utils;

class ArrayParser
{
    public static function get($array, $path, $default = null, $reindex = false)
    {
        if (empty($path) || trim($path) === "") {
            return $array;
        }

        return self::arrayGet($array, self::explodePath($path), $default, $reindex);
    }

    public static function set(array &$array, $path, $value)
    {
        self::arraySet($array, self::explodePath($path), $value);

        return $value;
    }

    public static function delete(array &$array, $path): bool
    {
        return self::arrayDelete($array, self::explodePath($path));
    }

    public static function isEmpty($array, $path, $reindex = false): bool
    {
        return empty(self::get($array, $path, null, $reindex));
    }

    public static function length($array, $path, $default = null, $reindex = false): ?int
    {
        $item = self::get($array, $path, $default, $reindex);

        if (is_array($item) || is_object($item)) {
            return count((array)$item);
        }

        if (is_string($item)) {
            return (int)strlen($item);
        }

        if (is_numeric($item)) {
            return (int)$item;
        }

        return null;
    }

    public static function exists($array, $path, $find = null, $reindex = false): bool
    {
        $result = self::get($array, $path, null, $reindex);

        if (!is_null($result) && is_null($find)) {
            return true;
        }

        if ((is_array($result) && in_array($find, $result))
            || (is_array($result) && isset($result[$find]))
            || (is_string($result) && str_contains($result, $find))) {
            return true;
        }

        return false;
    }

    public static function append(array &$array, $path, $value, $reindex = false, $default = null)
    {
        $item = self::get($array, $path, $default, $reindex);

        if ($item === null) {
            $item = $value;
        } elseif (is_array($item)) {
            $item[] = $value;
        } else {
            $item .= $value;
        }

        return self::set($array, $path, $item);
    }

    public static function merge(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $val) {
            if (array_key_exists($key, $array1) && is_array($val)) {
                $array1[$key] = self::merge($array1[$key], $val);
                continue;
            }

            $array1[$key] = $val;
        }

        return $array1;
    }

    private static function arrayGet($array, $keys, $default = null, $reindex = false)
    {
        $array = self::castToArray($array);
        $key   = array_shift($keys);

        if ($reindex && isset($array[$key]) && is_array($array[$key])) {
            self::reindexKeys($array[$key]);
        }

        if (isset($array[$key]) && count($keys) === 0) {
            return $array[$key];
        }

        if (isset($array[$key]) && is_array($array[$key]) && count($keys) >= 1) {
            return self::arrayGet($array[$key], $keys, $default, $reindex);
        }

        return $default;
    }

    public static function flatten(array $array, array &$result, $reindex = false, $prefix = null, $removeEmptyValues = false): void
    {
        foreach ($array as $key => $value) {
            if (!is_null($prefix)) {
                $key = $prefix . '_' . $key;
            }

            if (is_array($value)) {
                if ($reindex) {
                    self::reindexKeys($value);
                }
                self::flatten($value, $result, $reindex, $key);
            } elseif (!$removeEmptyValues || ($removeEmptyValues && $value !== '')) {
                $result[$key] = $value;
            }
        }
    }

    public static function clearEmptyValues(array &$array, $reindex = false, $removeEmptyArrays = false): void
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!empty($value) && $reindex === true) {
                    self::reindexKeys($array[$key]);
                }

                self::clearEmptyValues($array[$key], $reindex, $removeEmptyArrays);

                if ($array[$key] === '' || (is_array($array[$key]) && empty($array[$key]))
                    && $removeEmptyArrays === true) {
                    unset($array[$key]);
                }

                continue;
            }

            if ($value === '') {
                unset($array[$key]);
            }
        }
    }

    public static function reindexKeys(array &$array): void
    {
        if (!self::hasStringKeys($array)) {
            $array = array_values($array);
        }
    }

    public static function hasStringKeys(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    private static function arraySet(array &$array, $keys, $value): void
    {
        $key = array_shift($keys);

        if (count($keys) == 0) {
            $array[$key] = $value;

            return;
        }

        if (count($keys) > 0) {
            if (!isset($array[$key])) {
                $array[$key] = [];
            }

            self::arraySet($array[$key], $keys, $value);
        }
    }

    private static function arrayDelete(array &$array, $keys): bool
    {
        $key = array_shift($keys);

        if (isset($array[$key]) && count($keys) == 0) {
            unset($array[$key]);
            return true;
        }

        if (isset($array[$key]) && count($keys) > 0) {
            return self::arrayDelete($array[$key], $keys);
        }

        return false;
    }

    private static function explodePath($path): array
    {
        return explode(':', trim($path, ':'));
    }

    private static function castToArray($object)
    {
        if (is_array($object)) {
            return $object;
        }

        if (empty($object)) {
            return [];
        }

        return json_decode($object, true);
    }
}
