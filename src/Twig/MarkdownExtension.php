<?php

namespace App\Twig;

use App\Twig\TokenParser\MarkdownTokenParser;
use Parsedown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', $this->markdown(...), ['is_safe' => ['html']]),
        ];
    }

    public function getTokenParsers(): array
    {
        return [new MarkdownTokenParser()];
    }

    public function markdown(string $string): string
    {
        return (new Parsedown())->text($string);
    }

    public function markdownToHtml(string $string): string
    {
        return $this->markdown($string);
    }
}
