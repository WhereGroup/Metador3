<?php

namespace App\Component\CatalogueService\Parameter;

use App\Component\CatalogueService\CswException;
use \App\Component\CatalogueService\Operation;


class PostDomParameter implements Parameter
{
    const EXTERNAL_PREFIX = 'my_prefix';

    /**
     * The requested xml
     */
    protected \DOMDocument $dom;

    /**
     * The xpath for a requested xml
     */
    protected \DOMXPath $xpath;

    protected string $rootPrefix;

    protected string $rootUri;

    public function __construct(string $content, ?string $rootPrefix = 'csw', ?string $rootUri = 'http://www.opengis.net/cat/csw/2.0.2')
    {
        $this->rootPrefix = $rootPrefix;
        $this->rootUri = $rootUri;
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($content, LIBXML_SCHEMA_CREATE)) {
            $errStr = $this->getErrorStr(libxml_get_errors());
            libxml_clear_errors();
            throw new CswException('Can\'t parse a string to xml: ' . $errStr, CswException::OPERATIONPARSINGFAILED);
        }
        if (($errStr = $this->getErrorStr(libxml_get_errors()))) {
            throw new CswException('Can\'t parse a string to xml: ' . $errStr, CswException::OPERATIONPARSINGFAILED);
            libxml_clear_errors();
        }
        $this->dom = $dom;
        $this->xpath = new \DOMXPath($this->dom);
        $nses = [];
        foreach ($this->xpath->query('//namespace::*') as $node) {
            if (strlen($node->prefix)) {
                $nses[$node->prefix] = $node->namespaceURI;
            } else {
                if ($node->namespaceURI === $this->rootUri) {
                    $nses[$node->prefix] = $node->namespaceURI;
                    $nses[$this->rootPrefix] = $node->namespaceURI;
                } else {
                    $nses[self::EXTERNAL_PREFIX] = $node->namespaceURI;
                }
            }
        }
        foreach ($nses as $ns => $uri) {
            $this->xpath->registerNamespace($ns, $uri);
        }
    }

    public function getOperationName(): string
    {
        return $this->dom->documentElement->localName;
    }

    public function initOperation(Operation $operation): Operation
    {
        $parameterMap = $operation->getPOSTParameterMap();
        $parameters = [];
        foreach ($parameterMap as $key => $value) {
            $parameters[$value] = $this->getParameter($key);
        }
        $operation->setParameters($parameters);

        return $operation;
    }

    protected function getParameter(string $xpath, ?\DOMElement $contextElm = null, ?bool $allowSingle = true): mixed
    {
        if (!$this->dom) {
            return null;
        }

        return $this->getValue($xpath, $contextElm, $allowSingle);
    }

    /**
     * Returns value for a given xpath
     */
    protected function getValue(string $xpath, ?\DOMElement $contextElm = null, ?bool $allowSingle = true): mixed
    {
        // DOMNodeList
        $list = $this->xpath->query($xpath, $contextElm ?? $this->dom->documentElement);
        if (!$list || $list->length === 0) {
            return null;
        } elseif ($list->length === 1 && $allowSingle) {
            return $this->getNodeValue($list->item(0));
        } else {
            $result = [];
            foreach ($list as $item) {
                $result[] = $this->getNodeValue($item);
            }

            return $result;
        }
    }

    /**
     * Returns the node value
     */
    protected function getNodeValue(\DOMNode $node): mixed
    {
        try {
            if (!$node) {
                return null;
            } elseif ($node->nodeType == XML_ATTRIBUTE_NODE) {
                return $node->value;
            } else {
                if ($node->nodeType == XML_TEXT_NODE) {
                    return $node->wholeText;
                } else {
                    if ($node->nodeType == XML_ELEMENT_NODE) {
                        return $node;
                    } else {
                        if ($node->nodeType == XML_CDATA_SECTION_NODE) {
                            return $node->wholeText;
                        } else {
                            return null;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getErrorStr(array $errors): string
    {
        if (count($errors) > 0) {
            $errorStrs = array_map(function ($e) {
                /* @var \LibXMLError $e */
                return $e->message;
            }, $errors);
            return implode(" , ", $errorStrs);
        } else {
            return "";
        }
    }
}
