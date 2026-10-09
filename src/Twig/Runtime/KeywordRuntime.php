<?php

namespace App\Twig\Runtime;

use App\Entity\Keyword;
use App\Repository\KeywordRepository;
use Twig\Extension\RuntimeExtensionInterface;

class KeywordRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly KeywordRepository $keywordRepository)
    {
    }

    public function getKeywordRepo(string $name): ?Keyword
    {
        return $this->keywordRepository->findOneBy(['identifier' => $name]);
    }

    public function getKeywordByProfile(string $profile): array
    {
        return $this->keywordRepository->getByProfile($profile);
    }
}

