<?php

namespace App\Twig;

use App\Twig\Runtime\KeywordRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class KeywordExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_keyword_repo', [KeywordRuntime::class, 'getKeywordRepo']),
            new TwigFunction('get_keywords', [KeywordRuntime::class, 'getKeywordByProfile']),
        ];
    }

    public function getName(): string
    {
        return 'metador_keys';
    }
}
