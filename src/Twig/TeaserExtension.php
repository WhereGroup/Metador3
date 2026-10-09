<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TeaserExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('teaser', $this->teaser(...)),
            new TwigFilter('groupname', $this->groupName(...))
        ];
    }

    public function teaser(string|null $string, int $maxLen = 100, bool $force = false): string
    {
        if (is_null($string)) return '';

        $teaser = '';
        $count = 0;

        if ($force) {
            $teaser = substr($string, 0, $maxLen);

            return $teaser . (strlen($string) === strlen($teaser) ? '' : ' ...');
        }

        $words = explode(' ', $string);

        foreach ($words as $word) {
            $count += strlen($word);

            if ($count >= $maxLen) {
                $teaser .= ' ...';
                break;
            }

            $teaser .= ' ' . $word;
        }

        return trim($teaser);
    }

    public function groupName(string $string): string
    {
        $result = "";
        $string = str_replace('ROLE_', '', $string);
        $string = str_replace('SYSTEM_', '', $string);
        $string = str_replace('_', ' ', $string);

        foreach (explode(" ", $string) as $word) {
            $result .= " " . ucfirst(strtolower($word));
        }

        return trim($result);
    }
}
