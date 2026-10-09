<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CoupledResourceExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $operatesOnUrl
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'generate_operates_on',
                $this->operatesOn(...),
                ['is_safe' => ['html']]
            )
        ];
    }

    public function operatesOn(string $uuid, array $coupledResource): string
    {
        $coupledResourceId = $coupledResource['codespace'] . $coupledResource['code'];

        return "\n" . '<srv:operatesOn xlink:href="'
            . urlencode($this->operatesOnUrl . $uuid . '#' . $coupledResourceId)
            . '" uuidref="' . $coupledResourceId . '" />';
    }
}
