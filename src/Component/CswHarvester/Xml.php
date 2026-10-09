<?php

namespace App\Component\CswHarvester;

class Xml
{
    const EXTERNAL_PREFIX = 'my_prefix';
    private $dom;
    private $xpath;
    protected $rootPrefix;
    protected $rootUri;

    private function __construct()
    {
    }

    public static function create($content, $rootPrefix = 'csw', $rootUri = 'http://www.opengis.net/cat/csw/2.0.2'): Xml
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($content, LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOENT | LIBXML_XINCLUDE)) {
            throw new \Exception('Can\'t parse a string to xml');
        }
        $xml = new Xml();
        $xml->rootPrefix = $rootPrefix;
        $xml->rootUri = $rootUri;
        $xml->dom = $dom;
        $xml->xpath = new \DOMXPath($xml->dom);
        $nses = [];
        foreach ($xml->xpath->query('//namespace::*') as $node) {
            if (strlen($node->prefix)) {
                $nses[$node->prefix] = $node->namespaceURI;
            } else {
                if ($node->namespaceURI === $xml->rootUri) {
                    $nses[$node->prefix] = $node->namespaceURI;
                } else {
                    $nses[self::EXTERNAL_PREFIX] = $node->namespaceURI;
                }
            }
        }
        foreach ($nses as $ns => $uri) {
            $xml->xpath->registerNamespace($ns, $uri);
        }
        return $xml;
    }

    public function getDom() {
        return $this->dom;
    }

    public function getValue($xpath, $contextElm = null, $allowSingle = true)
    {
        // DOMNodeList
        $list = $this->xpath->query($xpath, $contextElm ? $contextElm : $this->dom->documentElement);
        if ($list->length === 0) {
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

    protected function getNodeValue(\DOMNode $node)
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
}
