<?php

namespace App\Service\Search;


use App\Service\Metadata;

abstract class Search implements SearchInterface
{
    protected $hits       = null;
    protected $page       = null;
    protected $offset     = null;
    protected $terms      = null;
    protected $source     = null;
    protected $profile    = null;
    protected $groups     = null;
    protected $public     = null;
    protected $keyword    = null;
    protected $sort       = null;
    protected $parmeters  = null;

    /* @var Expression $expression */
    protected $expression = null;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    protected abstract function resetQuery();

    public function getParmeters()
    {
        return $this->parmeters;
    }

    public function setParmeters($parmeters): static
    {
        $this->parmeters = $parmeters;
        return $this;
    }

    public function getSort()
    {
        return $this->sort;
    }

    public function setSort($sort): static
    {
        $this->sort = $sort;
        return $this;
    }

    public function getKeyword()
    {
        return $this->keyword;
    }

    public function setKeyword($keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getTerms(): array
    {
        // Multiple space charakters in a search term provide multiple blank search terms
        return (array)preg_split('/\s+/', trim($this->terms));
    }

    public function setTerms($terms): static
    {
        $this->terms = $terms;

        return $this;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits): static
    {
        if (is_null($this->page)) {
            $this->page = 1;
        }

        $this->hits = (int)$hits;
        $this->offset = ($this->hits * $this->page) - $this->hits;

        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function setPage($page): static
    {
        if (is_null($this->hits)) {
            $this->hits = 10;
        }

        $this->page = (int)$page;
        $this->offset = ($this->hits * $this->page) - $this->hits;

        return $this;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setOffset($offset): static
    {
        $this->offset = (int)$offset;

        return $this;
    }

    public function getResultPaging(): \stdClass
    {
        $paging = new Paging(
            $this->getResultCount(),
            is_null($this->hits) ? 10 : $this->hits,
            is_null($this->page) ? 1 : $this->page
        );

        return $paging->calculate();
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

    public function getProfile()
    {
        return $this->profile;
    }

    public function setProfile($profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setPublic($public): static
    {
        $this->public = (boolean)$public;

        return $this;
    }

    public function setGroups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    public function setExpression(Expression $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    abstract public function getResultCount();

    abstract public function createExpression(): ExprHandlerInterface;

    abstract public function find();
}
