<?php

namespace App\Service\CswHarvester;

use App\Repository\CswHarvesterRepository;

class CswHarvester
{
    private CswHarvesterRepository $repo;

    public function __construct(
        CswHarvesterRepository $repo
    ) {
        $this->repo = $repo;
    }

    public function __destruct()
    {
        unset($this->repo);
    }

    public function all(): array
    {
        return $this->repo->findAll();
    }

    public function get($id)
    {
        return $this->repo->find($id);
    }

}