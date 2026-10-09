<?php

namespace App\Component\Map\XmlUtils;

interface IContextReader
{
    public function reset();
    public function startElement();
    public function endElement();
    public function addText();
    public function getContent();
}
