<?php

namespace App\Component\Utils\Import\Context;

interface Context
{
    public function getService();
    public function setService($service);
}
