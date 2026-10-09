<?php

namespace App\Service;

use App\Repository\ConfigurationRepository;

interface ConfigurationInterface
{
    public function __construct(ConfigurationRepository $repo, Cache $cache);
    public function set($key, $value, $filterType = '', $filterValue = '');
    public function get($key, $filterType = null, $filterValue = null, $default = null);
    public function remove($key, $filterType = null, $filterValue = null);
    public function getAll($filterType = null, $filterValue = null);
    public function getValues($filterType = null, $filterValue = null);
    public function removeAll($filterType = null, $filterValue = null);
}
