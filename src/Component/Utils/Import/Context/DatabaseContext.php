<?php

namespace App\Component\Utils\Import\Context;

class DatabaseContext implements Context
{
    private $service;
    private $source;
    private $entityName;
    private $filter = [];

    public function getService()
    {
        return $this->service;
    }

    public function setService($service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function setFilter($filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function getEntityName()
    {
        return $this->entityName;
    }

    public function setEntityName($entityName): static
    {
        $this->entityName = $entityName;

        return $this;
    }
}
