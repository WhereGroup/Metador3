<?php

namespace App\Component\Metadata;

use App\Component\Utils\ArrayParser;
use App\Service\Configuration;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Component\Exceptions\MetadataException;
use App\Event\MetadataValidationEvent;

class Validator
{
    /** @var  Plugin */
    protected $plugin;

    /** @var Configuration  */
    private $conf;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    protected $excludeListFromTest = [];

    public function __construct(Plugin $plugin, Configuration $conf, EventDispatcherInterface $eventDispatcher)
    {
        $this->plugin = $plugin;
        $this->conf   = $conf;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function excludeListFromTest($key): static
    {
        $this->excludeListFromTest[] = $key;
        return $this;
    }

    public function isValid(array $p, &$debug = []): bool
    {
        if ((empty($p['_public']))) {
            return true;
        }

        if (!isset($p['_profile'])) {
            throw new MetadataException("Profile missing");
        }

        $rules = json_decode($this->plugin->getResource($p['_profile'], 'validation.json'), true);

        if (!is_array($rules)) {
            throw new MetadataException("Can not decode validation.json");
        }

        $event = new MetadataValidationEvent($p, $rules, $debug);

        $this->eventDispatcher->dispatch($event, 'metadata.pre_validation');
        $this->objectIsValid($p, $rules, $debug);
        $this->eventDispatcher->dispatch($event, 'metadata.post_validation');

        return !$debug['hasErrors'];
    }

    public function objectIsValid($p, $rules, &$debug): bool
    {
        $metadataObject = $p;
        $tempRules = $rules;

        $this->prepairObject($metadataObject);

        if (!isset($debug['hasErrors'])) {
            $debug['hasErrors'] = false;
        }

        $this->testObject($metadataObject, $rules, $tempRules, $debug, [], $p['_profile']);

        if (!is_null($debug) && is_array($tempRules) && !empty($tempRules)) {
            foreach ($tempRules as $key => $rules) {
                foreach ($rules as $index => $rule) {
                    // Continue if rule is not for the backend or one of the following assert types.
                    if ((isset($rule['backend']) && $rule['backend'] === false)
                        || (isset($rule['assert']) && !in_array($rule['assert'], [
                            'notBlank', 'oneIsMandatory', 'onlyOne'
                        ]))) {
                        continue;
                    }

                    $debug['object'][$key][$index] = [
                        'test'        => $rule,
                        'missingData' => true
                    ];

                    $debug['hasErrors'] = true;
                    $debug['messages'][] = [
                        'key'     => $key,
                        'message' => $rule['message']
                    ];
                }
            }
        }

        return !(boolean)$debug['hasErrors'];
    }

    /**
     * @param $profile
     * @param $key
     * @param $val
     * @param $debug
     */
    private function testListOption($profile, $key, $val, &$debug)
    {
        if (in_array($key, $this->excludeListFromTest)) {
            return;
        }

        if (is_array($val)) {
            foreach ($val as $string) {
                if (empty($string)) {
                    continue;
                }

                $list = $this->conf->get($key, 'list-option', $profile);
                $array = !is_array($string) ? [$string] : $string;
                unset($string);

                foreach ($array as $string2) {
                    if (is_array($list) && !isset($list[$string2])) {
                        $debug['hasErrors'] = true;
                        $debug['messages'][] = [
                            'key'     => $key,
                            'message' => "Der Wert '" . $string2 . "' ist in der Liste nicht enthalten."
                        ];
                    }
                }
            }
            return;
        }

        $list = $this->conf->get($key, 'list-option', $profile);

        if (is_array($list) && !isset($list[$val])) {
            $debug['hasErrors'] = true;
            $debug['messages'][] = [
                'key'     => $key,
                'message' => "Der Wert '" . $val . "' ist in der Liste nicht enthalten."
            ];
        }
    }

    private function testObject(&$p, $rules, &$tempRules, &$debug, $parent = [], $profile = ""): void
    {
        foreach ($p as $key => &$val) {
            // Test List
            $this->testListOption($profile, $key, $val, $debug);

            if (is_array($val) && ArrayParser::hasStringKeys($val)) {
                $this->testObject($p[$key], $rules, $tempRules, $debug, $val, $profile);
                continue;
            }

            if (isset($rules[$key])) {
                unset($tempRules[$key]);

                foreach ($rules[$key] as $index => $rule) {
                    $debug['object'][$key][$index] = ['test' => $rule, 'untested' => true, 'invalid' => true];

                    // notBlank
                    if (isset($rule['assert']) && $rule['assert'] === 'notBlank') {
                        unset($debug['object'][$key][$index]['untested']);

                        if ($this->isNotEmpty($val)) {
                            unset($debug['object'][$key][$index]['invalid']);

                            continue;
                        }
                    // allOrNothing
                    } elseif (isset($rule['assert']) && $rule['assert'] === 'allOrNothing') {
                        unset($debug['object'][$key][$index]['untested']);

                        $count = $this->countEmptyElements($p, $parent, $rule);

                        if (($this->isNotEmpty($val) && $count === 0)
                            || ($this->isEmpty($val) && $count === count($rule['siblings']))) {
                            unset($debug['object'][$key][$index]['invalid']);
                            continue;
                        }
                    // onlyOne
                    } elseif (isset($rule['assert']) && $rule['assert'] === 'onlyOne') {
                        unset($debug['object'][$key][$index]['untested']);

                        $count = $this->countNotEmptyElements($p, $parent, $rule);

                        if (($this->isNotEmpty($val) && $count === 0) || ($this->isEmpty($val) && $count === 1)) {
                            unset($debug['object'][$key][$index]['invalid']);
                            continue;
                        }
                    // mandatoryIfSiblingNotEmpty
                    } elseif (isset($rule['assert']) && $rule['assert'] === 'mandatoryIfSiblingNotEmpty') {
                        unset($debug['object'][$key][$index]['untested']);

                        $count = $this->countNotEmptyElements($p, $parent, $rule);

                        if (($count >= 1 && $this->isNotEmpty($val)) || $count === 0) {
                            unset($debug['object'][$key][$index]['invalid']);
                            continue;
                        }
                    } elseif (isset($rule['assert']) && $rule['assert'] === 'testSiblings') {
                        unset(
                            $debug['object'][$key][$index]['untested'],
                            $debug['object'][$key][$index]['invalid']
                        );
                        continue;
                    // oneIsMandatory
                    } elseif (isset($rule['assert']) && $rule['assert'] === 'oneIsMandatory') {
                        unset($debug['object'][$key][$index]['untested']);

                        $count = $this->countNotEmptyElements($p, $parent, $rule);

                        if ($this->isNotEmpty($val) || $count >= 1) {
                            unset($debug['object'][$key][$index]['invalid']);
                            continue;
                        }
                    // regex
                    } elseif (isset($rule['regex'])) {
                        unset($debug['object'][$key][$index]['untested']);

                        if (preg_match("/" . $rule['regex'] . "/", $val)) {
                            unset($debug['object'][$key][$index]['invalid']);
                            continue;
                        }
                    }

                    $debug['hasErrors'] = true;
                }

                continue;
            }

            $debug['noRules'][$key] = 'NO RULE!';
        }
    }

    private function isEmpty($value): bool
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '';
    }

    private function isNotEmpty($value): bool
    {
        return !$this->isEmpty($value);
    }

    private function countEmptyElements(&$arr1, &$arr2, $rule)
    {
        if (empty($rule['siblings'])) {
            return false;
        }

        $count = 0;

        foreach ($rule['siblings'] as $key) {
            // Search in siblings
            if (is_array($arr2) && !empty($arr2)) {
                if ((isset($arr2[$key]) && $this->isEmpty($arr2[$key])) || !isset($arr2[$key])) {
                    ++$count;
                }

                if (!isset($arr2[$key])) {
                    $arr2[$key] = '';
                }
                continue;
            }

            // Search in parent array
            if ((isset($arr1[$key]) && $this->isEmpty($arr1[$key])) || !isset($arr1[$key])) {
                ++$count;
            }

            if (!isset($arr1[$key])) {
                $arr1[$key] = '';
            }
        }

        return $count;
    }

    private function countNotEmptyElements(&$arr1, &$arr2, $rule): bool|int
    {
        $count = $this->countEmptyElements($arr1, $arr2, $rule);

        if ($count === false) {
            return false;
        }

        return count($rule['siblings']) - $count;
    }

    public function prepairObject(&$array, $subkey = 'p_'): void
    {
        foreach ($array as $key => $val) {
            if (str_starts_with($key, '_')) {
                unset($array[$key]);
                continue;
            }

            $validatorArrayKey = $subkey . strtolower($key);
            $validatorKey = preg_replace('/(_[\d]+_?)/s', '', $validatorArrayKey);

            $keys[$validatorKey] = null;

            if (is_array($val)) {
                $array[$validatorArrayKey] = $val;
                unset($array[$key]);

                $this->prepairObject($array[$validatorArrayKey], $validatorKey . '_');
                continue;
            }

            if (isset($array[$validatorKey]) && is_array($array[$validatorKey])) {
                $array[$validatorKey][] = $val;
            } elseif (isset($array[$validatorKey]) && is_string($array[$validatorKey])) {
                $temp = $array[$validatorKey];
                unset($array[$validatorKey]);

                $array[$validatorKey][] = $temp;
                $array[$validatorKey][] = $val;
                unset($temp);
            } else {
                $array[$validatorKey] = $val;
            }

            unset($array[$key]);
        }
    }
}
