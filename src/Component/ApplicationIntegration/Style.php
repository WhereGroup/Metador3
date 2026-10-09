<?php

namespace App\Component\ApplicationIntegration;

/**
 * Class Style
 * @package App\Component\ApplicationIntegration
 */
class Style extends Base
{
    protected $type  = 'app-css';

    /**
     * @param $file
     * @param null $prefix
     * @return $this
     */
    public function file($file, $prefix = null)
    {
        $this->data[$this->generatePrefix($prefix)]['file'] = $file;
        return $this;
    }
}
