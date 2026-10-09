<?php

namespace App\Service\Search;

use App\Component\Exceptions\MetadataException;
use App\Entity\Metadata as MetadataEntity;
use App\Service\Metadata;
use SolrClientException;
use SolrObject;
use SolrQuery;
use SolrQueryResponse;
use SolrServerException;

class SolrSearch extends Search implements SearchInterface
{
    protected SolrMetadata $solr;
    protected SolrQuery $query;
    protected ?Expression $spatial = null;
    protected bool $prepared = false;

    public function __construct(SolrMetadata $solr, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->solr = $solr;
        $this->query = $solr->newQuery();
    }

    public function getSolr(): SolrMetadata
    {
        return $this->solr;
    }

    protected function resetQuery(): void
    {
        $this->query = $this->solr->newQuery();
    }

    public function setSpatialExpression(Expression $spatial): void
    {
        $this->spatial = $spatial;
    }

    public function find(): array
    {
        $this->prepareQuery();
        $response = $this->doRequest();

        $paging = new Paging(
            ($response->response->numFound ?? 0),
            is_null($this->hits) ? 10 : $this->hits,
            is_null($this->page) ? 1 : $this->page
        );

        $facet = [];

        if (isset($response->facet_counts->facet_fields)) {
            $facet = json_decode(json_encode($response->facet_counts->facet_fields), true);
        }

        return [
            'paging' => $paging->calculate(),
            'rows' => $this->prepareResults(
                isset($response->response->docs) && !empty($response->response->docs) ? $response->response->docs : []),
            'facet' => $facet
        ];
    }

    protected function prepareResults(array $results): array
    {
        $objects = [];
        foreach ($results as $row) {
            // no inheritance for a metadata list in the client: only the search from a client sets the parameters.
            if ($this->parmeters) {
                $objects[] = ['object' => json_decode($row->object, true)];
            } else {
                $objects[] = ['object' => $this->prepareObject($row)];
            }
        }
        return $objects;
    }

    public function prepareObject($row)
    {
        $dbRow = $this->metadata->findById($row->id);
        if ($this->hasParentIdentifier($dbRow)) {
            $parent = $this->metadata->findById($dbRow->getParent());
            if ($parent) {
                $p = $dbRow->getObject();
                $this->metadata->handleInheritance($p, $parent->getObject());
                return $p;
            }
        }

        return $dbRow->getObject();
    }

    public function getResultCount(): int
    {
        if (!$this->prepared) {
            $this->prepareQuery();
        }
        try {
            $response = $this->setPage(1)->setHits(0)->doRequest();
            return $response->response->numFound ?? 0;
        } catch (SolrServerException|MetadataException $e) {
        }
        return 0;
    }

    public function createExpression(): SolrExprHandler
    {
        return new SolrExprHandler();
    }

    protected function prepareQuery(): void
    {
        $this->prepared = false;
        $query = "*";

        if ($this->expression) {
            $this->query->addFilterQuery($this->expression->getExpression());
        }

        if ($this->spatial) {
            $this->query->addFilterQuery($this->spatial->getExpression());
        }

        if (isset($this->parmeters['productGroup']) && is_array($this->parmeters['productGroup'])) {
            foreach ($this->parmeters['productGroup'] as $key => $val) {
                if ($val !== 'true') {
                    continue;
                }
                $this->query->addFilterQuery('productGroup:' . '"' . $key . '"');
            }
        }

        // Searchterms
        if (trim($this->terms) !== "") {
            $termsStr = trim(preg_replace('/[:\-\|\(\)\/]/', ' ', strtolower($this->terms)));
            $terms = (array)preg_split('/\s+/', trim($termsStr));
            if (count($terms) > 0) {
                $query = "";
                foreach ($terms as $term) {
                    $query .= ' *' . $term . '*';
                }
            }
        }

        if (!empty($this->getSource())) {
            $this->query->addFilterQuery('source:' . $this->getSource());
        }

        if (!is_null($this->public)) {
            $this->query->addFilterQuery('public:' . (int)$this->public);
        }

        if (!empty($this->getProfile())) {
            if (is_array($this->getProfile())) {
                $filter = "";
                foreach ($this->getProfile() as $profile) {
                    $filter .= " OR profile:" . $profile;
                }

                $this->query->addFilterQuery(substr($filter, 4));
                unset($filter);
            } elseif (is_string($this->getProfile())) {
                $this->query->addFilterQuery('profile:' . $this->getProfile());
            }
        }

        if (!empty($this->getKeyword())) {
            if (is_array($this->getKeyword())) {
                $filter = "";
                foreach ($this->getKeyword() as $item) {
                    $filter = " OR keywords:" . $item;
                }

                $this->query->addFilterQuery(substr($filter, 4));
                unset($filter);
            } elseif (is_string($this->getKeyword())) {
                $this->query->addFilterQuery('keywords:' . $this->getKeyword());
            }
        }

        if (!is_null($this->offset)) {
            $this->query->setStart($this->offset);
        }

        if (!is_null($this->hits)) {
            $this->query->setRows($this->hits);
        }

//        if (!empty($this->getSort())) {
//            $order = SolrQuery::ORDER_ASC;
//
//            if (is_string($this->getSort())) {
//                $sort = 'titleFilter';
//                if ($this->getSort() !== 'title') {
//                    $sort = $this->getSort();
//
//                    if ($this->getSort() === 'date' || $this->getSort() === 'dateStamp') {
//                        $order = SolrQuery::ORDER_DESC;
//                    }
//                }
//                $this->query->addSortField($sort, $order);
//            } elseif (is_array($this->getSort())) {
//                foreach ($this->getSort() as $sort) {
//                    $order = SolrQuery::ORDER_ASC;
//
//                    if ($sort === 'title') {
//                        $sort = 'titleFilter';
//                    }
//
//                    if ($this->getSort() === 'date' || $this->getSort() === 'dateStamp') {
//                        $order = SolrQuery::ORDER_DESC;
//                    }
//
//                    $this->query->addSortField($sort, $order);
//                }
//            }
//        }

        if (!empty($this->getSort())) {
            $sortBy = is_array($this->getSort()) ? $this->getSort() : [$this->getSort()];
            foreach ($sortBy as $sort) {
                $params = explode(' ', $sort);
                $column = $params[0];
                $order = count($params) == 2 ? strtolower($params[1]) : 'asc';
                $this->query->addSortField($column, $order === 'desc' ? SolrQuery::ORDER_DESC : SolrQuery::ORDER_ASC);
            }
        }
        // add search address to query
        $q = trim($query);
        $this->query->addFilterQuery("(searchfield:{$q}) OR ({!join from=id to=address_id fromIndex=address}searchfield:{$q})");

        $this->query->setQuery('*:*');
        $this->prepared = true;
        $f = $this->query->getFilterQueries();
        $a = 0;
    }

    protected function doRequest(): SolrObject
    {
        if (!$this->prepared) {
            $this->prepareQuery();
        }
        /** @var SolrQueryResponse $queryResponse */
        try {
            $queryResponse = $this->solr->client->query($this->query);
        } catch (SolrClientException $e) {
            throw new MetadataException('Solr-Server nicht erreichbar.');
        }

        /** @var SolrObject $response */
        return $queryResponse->getResponse();
    }

    private function hasParentIdentifier(MetadataEntity $row): bool
    {
        return (isset($row->getObject()['parentIdentifier']) && !empty($row->getObject()['parentIdentifier']));
    }
}
