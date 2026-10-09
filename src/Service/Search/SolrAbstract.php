<?php

namespace App\Service\Search;

use App\Service\Logger;
use SolrClient;
use SolrClientException;
use SolrIllegalArgumentException;
use SolrInputDocument;
use SolrQuery;
use SolrServerException;

abstract class SolrAbstract
{
    public SolrClient $client;
    public SolrQuery $query;
    public string $host;
    public int $port;
    public string $path;
    protected Logger $logger;

    /**
     * @throws SolrIllegalArgumentException
     */
    public function __construct(
        string $host,
        int $port,
        string $path,
        Logger $logger
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->logger = $logger;

        if (!empty($host) && !empty($port) && !empty($path)) {
            $this->client = $this->createClient();

            $this->query = new SolrQuery();
        }
    }

    /**
     * @throws SolrIllegalArgumentException
     */
    public function createClient(): SolrClient
    {
        return new SolrClient([
            'hostname' => $this->host,
            'port'     => $this->port,
            'path'     => $this->path
        ]);
    }

    public function isActive(): bool
    {
        if (is_object($this->client)) {
            return true;
        }

        return false;
    }

    public function isOnline(): bool
    {
        return $this->ping();
    }

    public function ping(): bool
    {
        try {
            $this->client->ping();
            return true;
        } catch (SolrServerException|SolrClientException $e) {
        }

        return false;
    }

    public function newQuery(): SolrQuery
    {
        return new SolrQuery();
    }

    public function newDocument(): SolrInputDocument
    {
        return new SolrInputDocument();
    }

    public function count()
    {
        $this->query
            ->setQuery('*:*')
            ->setStart(1)
            ->setRows(0);

        return ($this->client->query($this->query))->getResponse()->response->numFound;
    }

    public abstract function update(mixed $metadata, $options = []): void;

    protected abstract function log($uuid, $message, $flush): void;
}