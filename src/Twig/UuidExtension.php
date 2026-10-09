<?php

namespace App\Twig;

use Ramsey\Uuid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UuidExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('uuid', $this->uuid(...)),
        ];
    }

    public function uuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}
