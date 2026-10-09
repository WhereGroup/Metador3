<?php

namespace App\Service;

use App\Repository\ConfigurationRepository;

readonly class Configuration implements ConfigurationInterface
{
    public function __construct(private ConfigurationRepository $repo, private Cache $cache)
    {
    }

    public function set($key, $value, $filterType = '', $filterValue = ''): static
    {
        $this->repo->set($key, $value, $filterType, $filterValue);
        $this->cache->set($this->generateKey($key, $filterType, $filterValue), $value);
        return $this;
    }

    public function get($key, $filterType = null, $filterValue = null, $default = null)
    {
        $cacheKey = $this->generateKey($key, $filterType, $filterValue);
        $value    = $this->cache->get($cacheKey);

        if ($value === false) {
            $value = $this->repo->getValue($key, $filterType, $filterValue, $default);
            if (!is_null($value)) {
                $this->cache->set($cacheKey, $value);
            }
            return $value;
        }

        if (is_object($value)) {
            return (array)$value;
        }

        return $value;
    }

    public function remove($key, $filterType = null, $filterValue = null): static
    {
        $this->repo->remove($key, $filterType, $filterValue);
        $this->cache->delete($this->generateKey($key, $filterType, $filterValue));
        return $this;
    }

    public function getAll($filterType = null, $filterValue = null)
    {
        $cacheKey = $this->generateKey('', $filterType, $filterValue);
        $values = $this->cache->get($cacheKey);

        if ($values === false) {
            $values = $this->repo->all($filterType, $filterValue);
            $this->cache->set($cacheKey, $values);
        }

        return $values;
    }

    public function getValues($filterType = null, $filterValue = null)
    {
        $cacheKey = $this->generateKey('', $filterType, $filterValue);
        $config = $this->cache->get($cacheKey);

        if ($config === false) {
            $config = [];

            foreach ($this->getAll($filterType, $filterValue) as $row) {
                $config[$row['key']] = $row['value'];
            }

            $this->cache->set($cacheKey, $config);
        }

        return $config;
    }

    public function removeAll($filterType = null, $filterValue = null): static
    {
        $this->repo->removeAll($filterType, $filterValue);
        $this->cache->truncate();
        return $this;
    }

    public function truncate(): static
    {
        $this->repo->truncate();
        $this->cache->truncate();
        return $this;
    }

    private function generateKey($key = '', $filterType = '', $filterValue = ''): string
    {
        return 'configuration-' . $key . '-' . $filterType . '-' . $filterValue;
    }
}
