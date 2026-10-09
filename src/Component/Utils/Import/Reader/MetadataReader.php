<?php

namespace App\Component\Utils\Import\Reader;

use App\Service\Search\JsonFilterReader;
use App\Service\Search\Search;
use Generator;
use RuntimeException;

class MetadataReader implements Reader
{
    /** @var Search */
    public $search;
    public $source;
    public $filter;

    public function open($context): MetadataReader|Reader|static
    {
        $reader = clone $this;

        $reader->search = $context->getService();
        $reader->source = $context->getSource();
        $reader->filter = $context->getFilter();

        return $reader;
    }

    public function read($offset = null, $limit = null): Generator
    {
        if (!$this->search) {
            throw new RuntimeException("Run open() method first.");
        }

        $this->search
            ->setHits($limit)
            ->setOffset($offset)
            ->setSource($this->source)
            ->setSort('id');

        if (!empty($this->filter)) {
            $this->search->setExpression(JsonFilterReader::read($this->filter, $this->search->createExpression()));
        }

        $rows = $this->search->find();

        if (!isset($rows['rows'])) {
            $rows['rows'] = [];
        }

        $count = 0;

        foreach ($rows['rows'] as $row) {
            yield $count => $row['object'];
            ++$count;
        }
    }

    public function close()
    {
        unset($this->search);
    }
}
