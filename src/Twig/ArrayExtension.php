<?php

namespace App\Twig;

use App\Component\Utils\ArrayParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArrayExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('array_get', $this->get(...)),
            new TwigFunction('array_is_empty', $this->isEmpty(...)),
            new TwigFunction('array_to_string', $this->toString(...)),
            new TwigFunction('array_length', $this->length(...)),
            new TwigFunction('array_exists', $this->exists(...)),
            new TwigFunction('array_all_exists', $this->allExists(...)),
            new TwigFunction('array_one_exists', $this->oneExists(...)),
            new TwigFunction('array_has_value', $this->arrayHasValue(...)),
            new TwigFunction('array_implode', $this->arrayToString(...)),
        ];
    }

    public function get(mixed $array, string $path, mixed $default = null, bool $reindex = false): mixed
    {
        if (!is_array($array)) {
            return null;
        }

        return ArrayParser::get($array, $path, $default, $reindex);
    }

    public function isEmpty(array $array, string $path, bool $reindex = false): bool
    {
        return ArrayParser::isEmpty($array, $path, $reindex);
    }

    public function toString(array $array): string
    {
        return implode(", ", $array);
    }

    public function length(array $array, string $path, mixed $default = null, bool $reindex = false): int|null
    {
        return ArrayParser::length($array, $path, $default, $reindex);
    }

    public function exists(array $array, string $path, mixed $find = null, bool $reindex = false): bool
    {
        return ArrayParser::exists($array, $path, $find, $reindex);
    }

    public function allExists(array $array, array $paths, mixed $find = null, bool $reindex = false): bool
    {
        foreach ($paths as $path) {
            if (!$this->exists($array, $path, $find, $reindex)) {
                return false;
            }
        }

        return true;
    }

    public function oneExists(array $array, array $paths, mixed $find = null, bool $reindex = false): bool
    {
        foreach ($paths as $path) {
            if (!ArrayParser::isEmpty($array, $path, $reindex)) {
                return true;
            }
        }

        return false;
    }

    public function arrayHasValue(mixed $array): bool
    {
        $option = false;
        if (is_array($array)) {
            array_walk_recursive($array, function ($item) use (&$option) {
                if ($item !== "") {
                    $option = true;
                }
            });
            return $option;
        }

        return !empty($array);
    }

    public function arrayToString(mixed $array): string
    {
        if (is_array($array)) {
            return implode("", $array);
        }

        return (string) $array;
    }
}
