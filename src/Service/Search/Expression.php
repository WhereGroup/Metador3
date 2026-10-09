<?php

namespace App\Service\Search;

class Expression
{
    private $expression;
    private $parameters;

    public function __construct($expression, $parameters)
    {
        $this->expression = $expression;
        $this->parameters = $parameters;
    }

    public function getExpression()
    {
        return $this->expression;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
