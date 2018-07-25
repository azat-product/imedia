<?php

require('Lite/Lite.php');

class CacheFile_ extends Cache
{
    /** @var Cache_Lite */
    protected $lite;

    public function init($aOptions = array(null))
    {
        parent::init();

        if (!is_array($aOptions)) {
            $aOptions = array();
        }

        $aDefaults = array(
            'cacheDir'               => PATH_BASE . 'files' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR,
            'automaticSerialization' => true,
            'lifeTime'               => null,
        );
        foreach ($aDefaults as $k => $v) {
            if (!isset($aOptions[$k])) {
                $aOptions[$k] = $v;
            }
        }
        $this->lite = new Cache_Lite($aOptions);
    }

    public function setLifeTime($time = null)
    {
        if (empty($time)) {
            $time = null;
        }
        $this->lite->setLifeTime($time);
    }

    protected function getValue($key)
    {
        return $this->lite->get($key, $this->groupName);
    }

    protected function setValue($key, $value, $expire)
    {
        if ($expire > 0) {
            $this->lite->setLifeTime($expire);
        }

        return $this->lite->save($value, $key, $this->groupName);
    }

    protected function addValue($key, $value, $expire)
    {
        return $this->setValue($key, $value, $expire);
    }

    protected function existsValue($key)
    {
        return ($this->getValue($key) !== false);
    }

    protected function deleteValue($key)
    {
        return $this->lite->remove($key, $this->groupName, true);
    }

    public function flush($groupName = false)
    {
        $this->lite->clean($groupName);
    }
}