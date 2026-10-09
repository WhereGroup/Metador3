<?php

namespace App\Service;

use App\Repository\SourceRepository;

interface SourceInterface
{
    public function __construct(SourceRepository $repo);

    public function __destruct();
}
