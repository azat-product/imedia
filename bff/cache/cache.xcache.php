<?php

class CacheXCache_ extends Cache
{
    public function init()
    {
        parent::init();

        if (!extension_loaded('xcache') || !function_exists('xcache_get')) {
            throw new \Exception('CacheXCache requires PHP XCache extension to be loaded.');
        }
    }

    protected function getValue($key)
    {
        return xcache_isset($key) ? xcache_get($key) : false;
    }

    protected function setValue($key, $value, $expire)
    {
        return xcache_set($key, $value, $expire);
    }

    protected function addValue($key, $value, $expire)
    {
        return xcache_isset($key) ? $this->setValue($key, $value, $expire) : false;
    }

    protected function existsValue($key)
    {
        return xcache_isset($key);
    }

    protected function deleteValue($key)
    {
        return xcache_unset($key);
    }

    public function flush($groupName = false)
    {
        if ($groupName !== false) {
            xcache_unset_by_prefix($groupName);
        } else {
            $cnt = xcache_count(XC_TYPE_VAR);
            for ($i=0; $i < $cnt; $i++) {
                xcache_clear_cache(XC_TYPE_VAR, $i);
            }
        }
    }
}