<?php

namespace App\Service;

use App\Component\Exceptions\MetadorException;

class Cache
{
    private $cache;
    private $enabled;

    public function __construct($host, $port, $enabled)
    {
        if ($enabled === 'false') {
            $this->enabled = false;
            return;
        }

        if (!class_exists('Memcached')) {
            throw new MetadorException("Memcached not found.");
        }

        $this->cache = new \Memcached('persistent');

        if (!$this->cache->addServer($host, $port)) {
            throw new MetadorException("Could not connect to memcached server.");
        }

        $this->enabled = $enabled;
    }

    public function get($key)
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->cache->get($key);
    }

    public function set($key, $value): false|static
    {
        if (!$this->enabled) {
            return false;
        }

        $this->cache->set($key, $value);
        return $this;
    }

    public function truncate(): false|static
    {
        if (!$this->enabled) {
            return false;
        }
        $this->cache->flush();
        return $this;
    }

    public function delete($key): false|static
    {
        if (!$this->enabled) {
            return false;
        }

        $this->cache->delete($key);
        return $this;
    }


    public function stats(): false|array
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->cache->getStats();
    }
}
