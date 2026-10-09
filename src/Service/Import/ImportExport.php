<?php

namespace App\Service\Import;

use App\Repository\ImportExportRepository;

class ImportExport
{
    protected $repo;
    protected $importFolder;
    protected $exportFolder;

    public function __construct(
        ImportExportRepository $repo,
        $importFolder,
        $exportFolder
    ) {
        $this->repo = $repo;
        $this->importFolder = $importFolder;
        $this->exportFolder = $exportFolder;
    }

    public function __destruct()
    {
        unset($this->repo, $this->importFolder, $this->exportFolder);
    }

    public function all(): array

    {
        return $this->repo->findAll();
    }

    public function get($id)
    {
        return $this->repo->findOneById($id);
    }

    public function getImportFolder()
    {
        return $this->importFolder;
    }

    public function getExportFolder()
    {
        return $this->exportFolder;
    }

    public function getCronString($entity): string
    {
        return trim(
            $entity->getMin() . ' ' .
            $entity->getHour() . ' ' .
            $entity->getDayOfMonth() . ' ' .
            $entity->getMonth() . ' ' .
            $entity->getDayOfWeek() . ' ' .
            $entity->getYear()
        );
    }
}
