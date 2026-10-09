<?php

namespace App\Component\ApplicationIntegration;

/**
 * Class AppInformation
 * @package App\Component\ApplicationIntegration
 */
class AppInformation extends GlobalMenu
{
    protected $type = 'app-information';

    /**
     * @param $count
     * @return $this
     */
    public function count($count)
    {
        $this->data[$this->prefix]['count'] = $count;
        return $this;
    }

    /**
     * @param $content
     * @return $this
     */
    public function content($content)
    {
        $this->data[$this->prefix]['content'] = $content;
        return $this;
    }
}
