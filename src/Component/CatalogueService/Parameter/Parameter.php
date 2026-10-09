<?php

namespace App\Component\CatalogueService\Parameter;

use App\Component\CatalogueService\Operation;

interface Parameter
{

    public function getOperationName(): string;

    public function initOperation(Operation $operation): Operation;
}
