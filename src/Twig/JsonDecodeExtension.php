<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class JsonDecodeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', $this->decode(...))
        ];
    }

    public function decode(string $var, bool $assoc = false): mixed
    {
        return json_decode($var, $assoc);
    }
}
