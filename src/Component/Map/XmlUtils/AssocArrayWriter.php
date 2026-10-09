<?php

namespace App\Component\Map\XmlUtils;

class AssocArrayWriter implements IContextWriter
{

    protected $content;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        ;
    }

    public function write(array $content)
    {
        return $content;
    }
}
