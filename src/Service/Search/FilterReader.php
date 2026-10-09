<?php

namespace App\Service\Search;

interface FilterReader
{
    public static function read(mixed $filter, ExprHandler $expression);
}
