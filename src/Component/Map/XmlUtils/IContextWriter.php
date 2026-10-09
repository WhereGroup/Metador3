<?php

namespace App\Component\Map\XmlUtils;

interface IContextWriter
{
    public function write(array $content);
    public function reset();
}
