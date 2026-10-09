<?php

namespace App\Component;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class XmlParser
{
    private DOMXPath $xp;
    private DOMDocument $doc;
    private XmlParserFunctions $functions;
    private mixed $schema = null;
    private bool $removeEmptyValues = true;
    private bool $trimValues = false;
    private ?array $cache = null;
    private bool $sort = false;

    public function __construct(string $xml, XmlParserFunctions $functions)
    {
        $this->doc = new DOMDocument();

        if (!@$this->doc->loadXML($xml) || !@$this->xp = new DOMXPath($this->doc)) {
            throw new RuntimeException('Error while loading the XML.');
        }

        $this->functions = $functions;
    }

    public function loadSchema(string $json): static
    {
        $array = $this->objectToArray(json_decode($json));

        if (empty($array) || !is_array($array)) {
            throw new RuntimeException('Empty or invalid schema.');
        }

        $current = $this->objectToArray($this->schema);
        if (!is_array($current)) {
            $current = [];
        }

        $this->schema = json_decode(json_encode($this->mergeRecursive($current, $array)));

        return $this;
    }

    public function registerNamespaces(array|object $namespaces): void
    {
        foreach ((array)$namespaces as $namespaceKey => $namespaceValue) {
            $this->xp->registerNamespace($namespaceKey, $namespaceValue);
        }
    }

    public function parse()
    {
        if (isset($this->schema->_cmd)) {
            foreach ($this->schema->_cmd as $key => $val) {
                if ($key === "_addNamespaces") {
                    $this->registerNamespaces($val);
                } elseif ($key === "_removeEmptyValues") {
                    $this->removeEmptyValues = (bool)$val;
                } elseif ($key === "_sortResult") {
                    $this->sort = (bool)$val;
                } elseif ($key === "_trimValues") {
                    $this->trimValues = (bool)$val;
                }
            }
        }

        $array = $this->parseRecursive($this->schema);

        if ($this->sort) {
            ksort($array);
        }

        return $array;
    }

    /**
     * @param $object
     * @param string $name
     * @param null $context
     * @param string $path
     * @param array $commands
     * @return array|mixed
     */
    private function parseRecursive($object, string $name = "", $context = null, string $path = "", array $commands = []): mixed
    {
        $result = [];

        $commands['_recursive'] = $commands['_recursive'] ?? false;
        $commands['_asArray']   = $commands['_asArray'] ?? false;
        $commands['_function']  = $commands['_function'] ?? null;

        foreach ($object as $key => $val) {
            switch ($key) {
                case "_":
                case "_cmd":
                    break;
                case "_path":
                    $path .= $val;
                    break;
                case "_asArray":
                    $commands['_asArray'] = $val;
                    break;
                case "_recursive":
                    $commands['_recursive'] = $val;
                    break;
                case "_function":
                    $commands['_function'] = $val;
                    break;
                case "_data":
                    if (is_string($val) && isset($this->cache[$val])) {
                        $result = $this->parseData($this->cache[$val], $name, $path, $context, $commands);
                    } else {
                        if (!isset($this->cache[$name])) {
                            $this->cache[$name] = $val;
                        }
                        $result = $this->parseData($val, $name, $path, $context, $commands);
                    }

                    if (!is_null($commands['_function'])) {
                        $result = $this->functions->get($commands['_function'], $result);
                    }

                    break;
                default:
                    if (is_object($val)) {
                        $tmp = $this->parseRecursive($val, $key, $context, $path, $commands);
                    } elseif (is_array($val) && count($val) >= 2) {
                        if (is_null($val[0])) {
                            $tmp = $this->functions->get($val[1], null, array_slice($val, 2));
                        } else {
                            $tmp = $this->getValue($path . $val[0], $context);
                            $tmp = $this->functions->get($val[1], $tmp, array_slice($val, 2));
                        }
                    } else {
                        $tmp = $this->getValue($path.$val, $context);

                        if (is_string($tmp) &&  $this->trimValues) {
                            $tmp = trim($tmp);
                        }
                    }

                    if ($this->removeEmptyValues && (
                        $tmp === "" || $tmp === [] || (
                            is_array($val) && isset($val[1]) && (
                                is_string($val[1]) && $val[1] !== '_asArray' ||
                                is_array($val[1]) && !in_array('_asArray', $val[1])
                            )
                        ))) {
                        continue 2;
                    }

                    $result[$key] = $tmp;
            }
        }

        return $result;
    }

    private function parseData($data, $name, $path, $context, $commands): array
    {
        if (!is_object($data) && !is_array($data)) {
            return [];
        }

        $tmp = [];

        if ($context == null) {
            $nodes = $this->xp->query($path);
        } else {
            $nodes = $this->xp->query($path, $context);
        }

        if ($nodes) {
            foreach ($nodes as $node) {
                $dataRecTmp = [];

                if ($commands['_recursive']) {
                    $dataRecTmp = $this->parseData($data, $name, $path, $node, $commands);
                }

                $dataTmp = $this->parseRecursive($data, $name, $node);

                $tmp[] = empty($dataRecTmp) ? $dataTmp : array_merge(
                    $dataTmp,
                    [$name => $dataRecTmp]
                );
            }
        }

        return $commands['_asArray'] ? $tmp : $this->getCleanArray($tmp);
    }

    private function getValue($xpath, $context = null): array|string
    {
        $result = [];

        $nodes = $this->xp->query($xpath, $context);

        if ($nodes) {
            foreach ($nodes as $node) {
                switch ($node->nodeType) {
                    case XML_ATTRIBUTE_NODE:
                        $result[] = $node->value;
                        break;
                    case XML_TEXT_NODE:
                        $result[] = $node->wholeText;
                        break;
                    case XML_CDATA_SECTION_NODE:
                        $result[] = $node->textContent;
                        break;
                    default:
                }
            }
        }

        return empty($result)
            ? ''
            : $this->getCleanArray($result);
    }

    private function getCleanArray(array $array): mixed
    {
        return count($array) === 1 ? $array[0] : $array;
    }

    private function objectToArray(mixed $object): mixed
    {
        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        if (!is_array($object)) {
            return $object;
        }

        $array = [];
        foreach ($object as $key => $val) {
            $array[$key] = (is_object($val) || is_array($val))
                ? $this->objectToArray($val)
                : $val;
        }

        return $array;
    }

    private function mergeRecursive(array $arr1, array $arr2): array
    {
        foreach (array_keys($arr2) as $key) {
            if (isset($arr1[$key]) && is_array($arr1[$key]) && is_array($arr2[$key])) {
                $arr1[$key] = $this->mergeRecursive($arr1[$key], $arr2[$key]);
            } else {
                $arr1[$key] = $arr2[$key];
            }
        }

        return $arr1;
    }
}
