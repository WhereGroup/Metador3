<?php

namespace App\Service;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CsvExport
{
    protected $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function __destruct()
    {
        unset($this->authorizationChecker);
    }

    public function buildCsvArray($rows) : array
    {
        $data = [];

        if (is_array($rows)) {
            $data[] = ['uuid', 'title'];

            foreach ($rows as $row) {
                $row = $row['object'];
                $data[] = [$row['_uuid'], $row['title']];
            }
        }

        return $data;
    }
}
