<?php

namespace App\Component\ApiRecords;

class Cql2TextToJsonFilerConverter
{

    private static function getFunTypes(array $functions): array
    {
        $result = [
            'logical' => [],
            'comparison' => [],
            'spatial' => [],
            'operator' => [],
            'function' => [],
//            'arithmetic' => [],
        ];
        foreach ($functions['functions'] as $function) {
            if (in_array($function['name'], ['and', 'or', 'not'])) {
                $result['logical'][] = $function['name'];
                continue;
            }
            if (in_array($function['name'], ['isNull', 'like', 'between', 'in', '<>', '<=', '>=', '<', '>', '='])) { # is "in" comparison?
                $result['comparison'][] = $function['name'];
                $result['operator'][] = $function['name'];
            } elseif (mb_strripos($function['name'], 's_') === 0) {
                $result['spatial'][] = $function['name'];
                $result['function'][] = $function['name'];
            } else {
                $result['function'][] = $function['name'];
            }
        }
        return $result;
    }

    public static function read(string $cql2Text, array $queryables, array $functions): array
    {
        $reader = new Cql2TextToJsonFilerConverter($queryables, $functions);

        return $reader->parse($cql2Text);
    }

    private $queryables;
    private $functions;
    private $functionTypes;
    private $stringVals = [];
    private $funVals = [];

    private function __construct(array $queryables, array $functions)
    {
        $this->queryables = $queryables;
        $this->functions = [];
        foreach ($functions['functions'] as $function) {
            $this->functions[$function['name']] = $function;
        }
        $this->functionTypes = self::getFunTypes($functions);
    }

    public function addValue(string $val): string
    {
        $placeholder = "'!" . (count(array_keys($this->stringVals))) . "!'";
        $this->stringVals[$placeholder] = $val;
        return $placeholder;
    }

    public function hasValue(string $key): bool
    {
        return isset($this->stringVals[$key]);
    }

    public function getValue(string $key): ?string
    {
        return isset($this->stringVals[$key]) ? $this->stringVals[$key] : null;
    }

    public function addFun(string $val): string
    {
        $placeholder = "'!F" . (count(array_keys($this->funVals))) . "F!'";
        $this->funVals[$placeholder] = $val;
        return $placeholder;
    }

    public function hasFun(string $key): bool
    {
        return isset($this->funVals[$key]);
    }

    public function getFun(string $key): ?string
    {
        return isset($this->funVals[$key]) ? $this->funVals[$key] : null;
    }

    public function getOperatorFuns(): array
    {
        return $this->functionTypes['operator'];
    }

    public function getArgsFuns(): array
    {
        return $this->functionTypes['function'];
    }

    public function isSpatialFun(string $fun)
    {
        return in_array($fun, $this->functionTypes['spatial']);
    }

    public function parse(string $text)
    {
        $bracketsGroups = $this->tokenizeBrackets($text);
        $result = $this->parseGroups($bracketsGroups);
//        return array_merge($bracketsGroups, $result, [$text]);
        return $result;
    }

    private function stringifyArray(array $arr): string
    {
        $s = '';
        foreach ($arr as $text) {
            if (is_array($text)) {
                $s .= '(' . $this->stringifyArray($text) . ')';
            } else {
                $s .= $text;
            }
        }
        return $s;
    }

    private function tokenizeBrackets(string $text, bool $cutString = true, bool $noTokenizeByFun = true): array
    {
        $i = 0;
        return $this->parseToList(mb_str_split(trim($text)), $i, $cutString, $noTokenizeByFun);
    }

    private function parseToList(array $s, int &$i, bool $cutString = true, bool $noTokenizeByFun = true)
    {
        $result = [];
        while ($i < count($s)) {
            if ($s[$i] == "'" && $cutString) {
                $this->parseString($s, $i, $result);
            } elseif ($s[$i] == '(') {
                $i++;
                $subResult = $this->parseToList($s, $i, $cutString, $noTokenizeByFun);
                if (!$noTokenizeByFun || count($result) === 0) {
                    $result[] = $subResult;
                    continue;
                }
                if (($fun = $this->endsWithFun($this->getOperatorFuns(), trim($result[count($result) - 1])))) {
                    $result[count($result) - 1] = $result[count($result) - 1] . '(' . $this->stringifyArray($subResult) . ')';
                } elseif (($fun = $this->endsWithFun($this->getArgsFuns(), trim($result[count($result) - 1])))) {
                    $result[count($result) - 1] = mb_substr($result[count($result) - 1], 0, mb_strripos($result[count($result) - 1], $fun))
                        . $this->addFun($fun . '(' . $this->stringifyArray($subResult) . ')');
                } elseif (($geomType = $this->endsWithGeomType($result[count($result) - 1]))) {
                    $result[count($result) - 1] = mb_substr($result[count($result) - 1], 0, mb_strripos($result[count($result) - 1], $geomType))
                        . $this->addValue($geomType . '(' . $this->stringifyArray($subResult) . ')');
                } else {
                    $result[] = $subResult;
                }
            } elseif ($s[$i] == ')') {
                $i++;
                return $result;
            } elseif (count($result) > 0 && !is_array($result[count($result) - 1])) {
                $result[count($result) - 1] .= $s[$i];
                $i++;
            } else {
                $result[] = $s[$i];
                $i++;
            }
        }
        return $result;
    }

    private function parseString(array $s, int &$i, array &$result)
    {
        $val = '';
        while ($s[++$i] !== "'" && $i < count($s)) {
            $val .= $s[$i];
            if ($s[$i] === '\\' && $s[$i + 1] === "'") {
                $val .= $s[++$i];
            }
        }
        ++$i;
        $placeholder = $this->addValue($val);
        if (count($result) > 0) {
            $result[count($result) - 1] = $result[count($result) - 1] . $placeholder;
        } else {
            $result[] = $placeholder;
        }
    }

    private function parseGroups(array $groups): array
    {
        for ($i = 0; $i < count($groups); $i++) {
            if (is_array($groups[$i])) {
                $groups[$i] = $this->parseGroups($groups[$i]);
            }
        }
        $before = null;
        for ($i = 0; $i < count($groups); $i++) {
            if (is_string($groups[$i])) {
                $text = mb_ereg_replace('\s+', ' ', trim($groups[$i]));
                $andPos = $this->getLogicalPositions($text, 'and');
                $orPos = $this->getLogicalPositions($text, 'or');
                if (count($andPos) > 0 || count($orPos) > 0) {
                    $after = null;
                    if ($i < count($groups) - 1) {
                        $after = $groups[$i + 1];
                        $i = $i + 1;
                    }
                    $before = $this->tokenizeLogicalOps($text, $andPos, $orPos, $before, $after);
                } else {
                    $before = $this->readFunction($text);
                }
            } else {
                $before = $groups[$i];
            }
        }
        return $before;
    }

    private function tokenizeLogicalOps(string $text, array $andPos, array $orPos, ?array $beforeExpression = null, ?array $afterExpression = null): array
    {
        $result = [];
        $all = array_merge($andPos, $orPos);
        asort($all);
        $last = null;
        $allCount = count($all);
        for ($i = 0; $i < $allCount; $i++) {
            $andOrOrPos = $all[$i];
            $andOrOr = in_array($andOrOrPos, $andPos) ? 'and' : 'or';
            if ($last === null) {
                $beforeText = trim(mb_substr($text, 0, $andOrOrPos));
                $result = [
                    'op' => $andOrOr,
                    'args' => [$beforeText ? $this->readFunction($beforeText) : $beforeExpression],
                ];
                $last = $andOrOr;
            } elseif ($last !== $andOrOr) {
                $result = [
                    'op' => $andOrOr,
                    'args' => [$result],// todo is correct?
                ];
                $last = $andOrOr;
            }
            $afterText = null;
            if ($i === $allCount - 1) {
                $afterText = trim(mb_substr($text, $andOrOrPos + mb_strlen($andOrOr)));
            } else {
                $length = $all[$i + 1] - ($andOrOrPos + mb_strlen($andOrOr));# (($i !== $allCount - 1) ? mb_strlen($andOrOr) : 0);
                $afterText = trim(mb_substr($text, $andOrOrPos + mb_strlen($andOrOr), $length));
            }
            $result['args'][] = $afterText ? $this->readFunction($afterText) : $afterExpression;
        }
        return $result;
    }

    private function readFunction(string $text): array
    {
        if ($this->hasFun(trim($text))) {
            return $this->readFunction($this->getFun($text));
        } elseif (mb_stripos($text, ' not ') !== false) {
//            $text_ = mberegi_replace(' not ', ' ', $text);
            $text_ = mb_ereg_replace(' not ', ' ', $text);
            $fun = $this->readFunction($text_);
            return $this->createFunction('not', [], [$fun]);
        } elseif (($pos = mb_stripos($text, ' is null')) !== false) {
            return $this->parseFunction('isNull', [trim(mb_substr($text, 0, $pos))]);
        } elseif (($fun = $this->startsWithFun($this->getArgsFuns(), $text))) {
            return $this->parseArgsFunction($fun, $text);
        } else {
            return $this->parseOperatorFunction($text);
        }
    }

    private function parseArgsFunction(string $funName, string $text): ?array
    {
        $chunks = preg_split('#\s?,\s?#', $this->stripFun($text, $funName));
        return $this->parseFunction($funName, $chunks);
    }

    private function parseFunction(string $funName, array $chunks)
    {
        $fun = $this->functions[$funName];
        $arguments = $fun['arguments'];
        if (count($chunks) !== count($arguments)) {
            throw new OgcApiException(OgcApiException::BADREQUEST, 'CQL Filter, function "' . $funName . '": the number of arguments isn\'t the correct');
        }
        $properties = [];
        $values = [];
        for ($i = 0; $i < count($arguments); $i++) {
            $chunk = trim($chunks[$i]);
            $argument = $arguments[$i];
            if (isset($this->queryables['properties'][$this->stripPropName($chunks[$i])])) {
                $prop = $this->stripPropName($chunks[$i]);
                $propDef = $this->queryables['properties'][$prop];
                if (isset($propDef['$ref'])) {
                    $properties[] = $prop; // todo
                } elseif ((is_string($argument['type']) && $propDef['type'] === $argument['type'])
                    || (is_array($argument['type']) && in_array($propDef['type'], $argument['type']))) {
                    $properties[] = $prop;
                } else {
                    throw new OgcApiException(OgcApiException::BADREQUEST, 'CQL Filter (function "' . $funName . '"): the type of the property "' . $prop . '" isn\'t the correct');
                }
            } elseif ($this->hasFun($chunk)) {
                $fun = $this->readFunction($this->getFun($chunk));
                $types = array_intersect($this->functions[$fun["op"]]['returns'], (is_array($argument['type']) ? $argument['type'] : [$argument['type']]));
                if (count($types) !== 0) {
                    $values[] = $fun;
                } else {
                    throw new OgcApiException(OgcApiException::BADREQUEST, 'CQL Filter (function "' . $funName . '"): the value type isn\'t the correct');
                }
            } else {
                $value = $this->hasValue($chunk) ? $this->getValue($chunk) : $chunk;
                if ($this->isSpatialFun($funName)) {
                    $value = $this->parseGeom($value);
                } elseif (in_array('array', $argument['type']) && count($argument['type']) === 1) {
                    $list = preg_split('#\s?,\s?#', preg_replace(['#^\(#', '#\)$#'], '', $value));
                    $items = [];
                    foreach ($list as $val) {
                        if ($this->hasValue($val)) {
                            $items[] = $this->getValue($val);
                        } else if ($this->hasFun($val)) {
                            $items[] = $this->readFunction($this->getFun($val));
                        } else {
                            $items[] = $this->getValid($val, $properties[0]);
                        }
                    }
                    $value = $items;
                }
                $values[] = $value;
            }
        }

        return $this->createFunction($funName, $properties, $values);
    }

    private function createFunction(string $funName, array $properties, array $values): array
    {

        $args = [];
        foreach ($properties as $prop) {
            $args[] = ['property' => $prop];
        }
        foreach ($values as $val) {
            $args[] = $val;
        }
        return [
            'op' => $funName,
            'args' => $args
        ];
    }

    private function stripPropName(string $text): string
    {
        return mb_ereg_replace('"', '', $text);
    }

    private function findFun(array $funNames, string $text): ?string
    {
        $result = null;
        foreach ($funNames as $funName) {
            if (mb_stripos($text, $funName) !== false) {
                $result = $result === null ? $funName : (mb_strlen($funName) > mb_strlen($result) ? $funName : $result);
            }
        }
        return $result;
    }

    private function parseOperatorFunction(string $text): array
    {
        $funName = $this->findFun($this->getOperatorFuns(), $text);
        if ($funName === null) {
            throw new OgcApiException(OgcApiException::BADREQUEST, "CQL Function isn't supported");
        }
        $chunks = preg_split('#\s*' . $funName . '\s*#i', $text);
        if ($funName === 'between') {
            $help = preg_split('#\s*and\s*#i', $chunks[1]);
            $chunks = [$chunks[0], $help[0], $help[1]];
        }
        return $this->parseFunction($funName, $chunks);
    }

    private function parseGeom(string $text)
    {
        $geomTypes = ['polygon'];
        foreach ($geomTypes as $geomType) {
            if ($this->startsWith($text, $geomType)) {
                $coordStr = trim(mb_substr($text, mb_strlen($geomType)));
                $tokenized = $this->tokenizeBrackets($coordStr);
                return $this->wktToGeoJson($geomType, $tokenized[0]);
            }
        }
        return null;
    }

    private function stripFun(string $text, string $fun)
    {
        return mb_eregi_replace('\)$', '', mb_eregi_replace('^' . $fun . '\s?\(', '', $text));
    }

    private function wktToGeoJson(string $gemType, array $coords): array
    {
        switch (mb_strtolower($gemType)) {
            case 'polygon':
                for ($i = 0; $i < count($coords); $i++) {
                    for ($j = 0; $j < count($coords[$i]); $j++) {
                        $coords[$i][$j] = $this->parseCoordinates($coords[$i][$j]);
                    }
                }
                return [
                    'type' => 'Polygon',
                    'coordinates' => $coords,
                ];
            default:
                throw new OgcApiException(OgcApiException::BADREQUEST, "GeomType '{$gemType}' is not supported ");
        }
    }

    private function parseCoordinates(string $coordList)
    {
        $result = [];
        foreach (preg_split('/\s*,\s*/', $coordList) as $coords) {
            $result[] = array_map(
                function ($value) {
                    return (float)$value;
                },
                preg_split('/\s/', $coords)
            );
        }
        return $result;
    }

    private function getLogicalPositions(string $text, string $term): array
    {

        $textlen = mb_strlen($text);
        $termlen = mb_strlen($term);
        if ($textlen === $termlen && mb_strtolower($text) === $term) {
            return [0];
        }
        $r = [];
        if (mb_stripos($text, $term . ' ', 0) === 0) {
            $r[] = 0;
        }

        if (($pos = mb_strripos($text, ' ' . $term)) === $textlen - $termlen - 1) {
            $r[] = $pos + 1;
        }
        $offset = 0;
        while (($pos = mb_stripos($text, ' ' . $term . ' ', $offset)) !== false) {
            $r[] = $pos + 1;
            $offset = $pos + 1;
        }
        asort($r);
        return array_unique($r);
    }

    private function endsWithFun(array $funNames, string $text): ?string
    {
        foreach ($funNames as $funName) {
            if ($this->endsWith($text, $funName)) {
                return $funName;
            }
        }
        return null;
    }

    private function endsWithGeomType(string $text): ?string
    {
        mb_eregi('^(.*\W|\s*)(point|multipoint|linestring|multilinestring|polygon|multipolygon)\s*$', $text, $matches);
        return count($matches) === 0 ? null : $matches[count($matches) - 1];
    }

    private function startsWithFun(array $funNames, string $text): ?string
    {
        foreach ($funNames as $funName) {
            if ($this->startsWith($text, $funName)) {
                return $funName;
            }
        }
        return null;
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        mb_eregi('^\s*(' . $needle . ')', $haystack, $matches);
        return count($matches) === 0 ? false : true;
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        mb_eregi('^(.*\W|\s*)(' . $needle . ')\s*$', $haystack, $matches);
        return count($matches) === 0 ? false : true;
    }

    private function getValid($value, string $key)
    {
        if ($key && isset($this->queryables['properties'][$key])) {
            return Query::validateSimple($this->queryables['properties'][$key], $value);
        } else {
            return Query::validateSimple($key, $value);
        }

//        throw new OgcApiException(OgcApiException::BADREQUEST, 'The parameter "' . $key . '" is not in schema properties');
    }
}
