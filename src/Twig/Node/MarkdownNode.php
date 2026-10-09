<?php

namespace App\Twig\Node;

use App\Twig\MarkdownExtension;
use Twig\Compiler;
use Twig\Node\Node;

class MarkdownNode extends Node
{
    public function __construct(Node $params, int $lineno = 0, ?string $tag = null)
    {
        parent::__construct(['params' => $params], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('ob_start();' . PHP_EOL)
            ->subcompile($this->getNode('params'))
            ->write('$content = ob_get_clean();' . PHP_EOL)
            ->write('preg_match("/^\s*/", $content, $matches);' . PHP_EOL)
            ->write('$lines = explode("\n", $content);' . PHP_EOL)
            ->write('$content = preg_replace(\'/^\' . $matches[0]. \'/\', "", $lines);' . PHP_EOL)
            ->write('$content = join("\n", $content);' . PHP_EOL)
            ->write('echo $this->env->getExtension(\'' . MarkdownExtension::class . '\')->markdownToHtml($content);' . PHP_EOL);
    }
}
