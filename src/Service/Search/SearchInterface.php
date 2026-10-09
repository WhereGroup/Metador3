<?php

namespace App\Service\Search;

interface SearchInterface
{
    public function createExpression(): ExprHandlerInterface;

    public function find();

    public function setExpression(Expression $expression): SearchInterface;

    public function setGroups(array $groups): SearchInterface;

    public function setHits($hits): SearchInterface;

    public function setPage($page): SearchInterface;

    public function setParmeters($parmeters): SearchInterface;

    public function getResultCount();

    public function setSort($sort): SearchInterface;

    public function setTerms($terms): SearchInterface;
}
