<?php

namespace App\Twig\TokenParser;

use App\Twig\Node\MarkdownNode;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class MarkdownTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        // recovers all inline parameters close to your tag name
        $params = array_merge([], $this->getInlineParams($token));

        $continue = true;
        while ($continue) {
            $body = $this->parser->subparse([$this, 'decideMarkdownFork']);
            $tag = $stream->next()->getValue();

            switch ($tag) {
                case 'endmarkdown':
                    $continue = false;
                    break;
                default:
                    throw new SyntaxError('Endtag for endmarkdown not found');
            }

            array_unshift($params, $body);
            $stream->expect(Token::BLOCK_END_TYPE);
        }

        return new MarkdownNode(new Node($params), $lineno, $this->getTag());
    }

    protected function getInlineParams(Token $token): array
    {
        $stream = $this->parser->getStream();
        $params = [];
        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $params[] = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(Token::BLOCK_END_TYPE);
        return $params;
    }

    public function decideMarkdownFork(Token $token): bool
    {
        return $token->test(['endmarkdown']);
    }

    public function getTag(): string
    {
        return 'markdown';
    }
}
