<?php

namespace App\Component\Utils\Import\Reader;

use Generator;

interface Reader
{
    public function open($context);

    public function read(): Generator;
}
