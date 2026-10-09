<?php

namespace App\Component\Utils\Import\Reader;

use Generator;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class FolderReader implements Reader
{
    /** @var Finder */
    public $finder;

    public function open($context): FolderReader|Reader|static
    {
        $reader = clone $this;
        $reader->finder = new Finder();
        $reader->finder
            ->files()
            ->name($context->getName())
            ->in($context->getFolder())
        ;
        return $reader;
    }

    public function read($offset = null, $limit = null): Generator
    {
        if (!$this->finder) {
            throw new RuntimeException("Run open() method first.");
        }

        $count = 0;
        $seek  = 1;

        foreach ($this->finder as $file) {
            if ($offset !== null && $seek < $offset) {
                ++$seek;
                continue;
            } elseif ($limit !== null && $count + 1 > $limit) {
                break;
            }
            yield $count => [
                'filename' => $file->getFilename(),
                'filepath' => $file->getRealPath(),
                'content'  => $file->getContents()
            ];
            ++$count;
        }
    }

    public function close(): void
    {
        unset($this->finder);
    }
}
