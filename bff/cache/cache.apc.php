<?php

class CacheApc_ extends Cache
{
    public function init()
    {
        parent::init();

        if (!extension_loaded('apc')) {
            throw new \Exception('CacheApc requires PHP apc extension to be loaded.');
        }
    }

    protected function getValue($key)
    {
        return apc_fetch($key);
    }

    protected function getValues($keys)
    {
        $resultsKeys = array();
        foreach ($keys as $key) {
            $resultsKeys[] = $key;
        }

        return apc_fetch($resultsKeys);
    }

    protected function setValue($key, $value, $expire)
    {
        return apc_store($key, $value, $expire);
    }

    protected function addValue($key, $value, $expire)
    {
        return apc_add($key, $value, $expire);
    }

    protected function existsValue($key)
    {
        return apc_exists($key);
    }

    protected function deleteValue($key)
    {
        return apc_delete($key);
    }

    public function flush($groupName = false)
    {
        $cacheType = 'user';
        if ($groupName === false) {
            return apc_clear_cache($cacheType);
        } else {
            if (class_exists('APCIterator')) {
                apc_delete(new \APCIterator($cacheType, '/^' . preg_quote($groupName) . '/'));
            }
        }
    }
}