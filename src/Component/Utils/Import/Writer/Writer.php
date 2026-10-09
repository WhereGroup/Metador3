<?php

namespace App\Component\Utils\Import\Writer;

use App\Component\Utils\Import\Context\Context;

interface Writer
{
    public function open(Context $context);

     public function write($items);
}
