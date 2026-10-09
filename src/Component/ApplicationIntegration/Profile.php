<?php

namespace App\Component\ApplicationIntegration;

/**
 * Class Profile
 * @package App\Component\ApplicationIntegration
 */
class Profile extends Base
{
    protected $type = 'app-profile';

    /**
     * @param $name
     * @return $this
     */
    public function name($name)
    {
        $this->data['name'] = $name;
        return $this;
    }

    /**
     * @param $active
     * @return $this
     */
    public function active($active)
    {
        $this->data['active'] = $active;
        return $this;
    }
}
