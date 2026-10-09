<?php

namespace App\Component\ApplicationIntegration;

/**
 * Class ManualMenu
 * @package App\Component\ApplicationIntegration
 */
class ManualMenu extends GlobalMenu
{
    protected $type = 'app-manual-menu';

    /**
     * @param $path
     * @return $this
     */
    public function index($path)
    {
        $this->data[$this->prefix]['index'] = $path;
        return $this;
    }
}
