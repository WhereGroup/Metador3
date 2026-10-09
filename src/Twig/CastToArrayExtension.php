<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CastToArrayExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('cast_to_array', $this->decode(...), ['is_safe' => ['html']])
        ];
    }

    public function decode(mixed $var): array
    {
        return (array) $var;
    }
}
