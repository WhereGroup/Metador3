<?php

namespace App\Component\Utils\Import\Context;

class FolderContext implements Context
{
    private $service;
    private $folder;
    private $name;
    private $overrideFile;

    public function getFolder()
    {
        return $this->folder;
    }

    public function setFolder($folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getService()
    {
        return $this->service;
    }

    public function setService($service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getOverrideFile()
    {
        return $this->overrideFile;
    }

    public function setOverrideFile($overrideFile): static
    {
        $this->overrideFile = $overrideFile;

        return $this;
    }
}
