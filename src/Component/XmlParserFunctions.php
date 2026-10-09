<?php

namespace App\Component;

use App\Component\Utils\ArrayParser;

class XmlParserFunctions
{
    public function get($methods, $data, $args = null)
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            if (method_exists($this, $method)) {
                $data = call_user_func('self::' . $method, $data, $args);
            }
        }

        return $data;
    }

    protected function _removeEmptyValues($data, $args = null)
    {
        ArrayParser::clearEmptyValues($data);
        return $data;
    }

    protected function _replaceKey($data, $args = null)
    {
        $array = [];

        foreach ($data as $key => $val) {
            if ($key === '#KEY#') {
                return $val;
            }

            if (is_array($val)) {
                $newKey = $this->_replaceKey($val);

                if ($newKey && !is_array($newKey)) {
                    unset($val['#KEY#']);
                    $array[$newKey] = $val;
                }
            }
        }

        return $array;
    }

    protected function _boolean($data, $args = null): bool
    {
        return !empty($data);
    }

    protected function _asText($data, $args = null)
    {
        return is_array($data) ? trim(implode(" ", $data)) : $data;
    }

    protected function _asArray($data, $args = null): array
    {
        return is_array($data) ? $data : [$data];
    }

    protected function _debug($data, $args = null): void
    {
        echo '<pre>' . print_r($data, 1) . '</pre>';
        die('<pre>' . print_r($args, 1) . '</pre>');
    }

    protected function _commaSeparated($data, $args = null)
    {
        return is_array($data) ? implode(", ", $data) : $data;
    }

    protected function _replace($data, $args = null): array|string
    {
        return str_replace($args[0], $args[1], $data);
    }

    protected function _string($data, $args = null)
    {
        return $args[0];
    }

    protected function _date($data, $args = null): string
    {
        return date(
            $args[0],
            $args[1] ?? time()
        );
    }
    /** @codingStandardsIgnoreEnd */
}
