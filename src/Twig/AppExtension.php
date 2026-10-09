<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', $this->jsonDecode(...), ['is_safe' => ['html']]),
        ];
    }

    public function jsonDecode(string $string, bool $assoc = false): mixed
    {
        return json_decode($string, $assoc);
    }
}
