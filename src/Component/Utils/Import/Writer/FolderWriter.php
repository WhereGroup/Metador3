<?php

namespace App\Component\Utils\Import\Writer;

use Exception;

class FolderWriter implements Writer
{
    public $folder;
    public $overrideFile;

    public function open($context): Writer|FolderWriter|static
    {
        $writer = clone $this;
        $writer->folder = rtrim($context->getFolder(), '/') . '/';

        if (!is_writable($writer->folder)) {
            throw new Exception("Order " . $writer->folder . " hat nicht die nötigen Schreibrechte.");
        }

        $writer->overrideFile = $context->getOverrideFile();
        return $writer;
    }

    public function write($items): void
    {
        foreach ($items as $uuid => $xml) {
            file_put_contents($this->folder . $uuid . '.xml', $xml);
        }
    }

    public function permissionToOverrideFile($uuid): bool
    {
        if ($this->overrideFile || !file_exists($this->folder . $uuid . '.xml')) {
            return true;
        }
        return false;
    }

    public function fileExists($uuid): bool
    {
        return file_exists($this->folder . $uuid . '.xml');
    }
}
