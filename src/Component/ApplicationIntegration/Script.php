<?php

namespace App\Component\ApplicationIntegration;

/**
 * Class Script
 * @package App\Component\ApplicationIntegration
 */
class Script extends Base
{
    protected $type  = 'app-javascript';

    /**
     * @param $file
     * @param null $prefix
     * @return $this
     */
    public function file($file, $prefix = null)
    {
        $this->data[$this->generatePrefix($prefix)] = $file;
        return $this;
    }
}
