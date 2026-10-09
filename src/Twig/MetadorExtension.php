<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MetadorExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('md_select', $this->isSelected(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('md_id', $this->getId(...), ['is_safe' => ['html']]),
            new TwigFilter('md_obj_id', $this->getObjectId(...), ['is_safe' => ['html']]),
            new TwigFilter('md_data_obj', $this->getDataObject(...), ['is_safe' => ['html']]),
            new TwigFilter('md_boolean', $this->booleanFilter(...)),
            new TwigFilter('md_array_path', $this->arrayPath(...)),
            new TwigFilter('md_first_key', $this->firstKey(...)),
        ];
    }

    public function getId(string $string): string
    {
        $string = str_replace(['[', ']'], ['_', '_'], strtolower($string));
        $string = str_replace("__", "_", $string);
        return ltrim(rtrim($string, "_"), "_");
    }

    public function firstKey(string $string): string
    {
        preg_match('/p\[([^\]]+)\].*/s', $string, $match);
        return $match[1] ?? '';
    }

    public function getObjectId(string $string): string
    {
        $string = preg_replace("/\[[\d]{1,2}\]/", "", strtolower($string));
        $string = str_replace(['[', ']'], ['_', '_'], $string);
        $string = str_replace("__", "_", $string);
        return trim($string, "\t\n\r_");
    }

    public function getDataObject(string $string): string
    {
        return 'data-obj-id="' . $this->getObjectId($string) . '"';
    }

    public function booleanFilter(mixed $value): string
    {
        if ($value === "1" || $value === true || $value === 1) {
            return "Ja";
        }

        return "Nein";
    }

    public function arrayPath(string $string): string
    {
        preg_match('/^p\[(.+)\]$/', $string, $match);

        if (empty($match[1])) {
            return '';
        }

        $string = str_replace([']['], ':', $match[1]);

        return trim($string, ':');
    }

    public function isSelected(mixed $value, mixed $option): string
    {
        return $value == $option ? 'selected=selected' : '';
    }
}
