<?php

namespace App\Component\Utils;

class Markdown
{
    private $parser;

    public function __construct()
    {
        $this->parser = new \Parsedown();
    }

    public function toHtml($text): string
    {
        return $this->parser->text($text);
    }
}
