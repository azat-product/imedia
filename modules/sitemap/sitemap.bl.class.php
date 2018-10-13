<?php

abstract class SitemapBase_ extends SitemapModule
{
    /** @var SitemapModel */
    public $model = null;

    public function init()
    {
        parent::init();
        $this->_replaceMacrosBeforeCache = false;
        $this->_useMetaSettings = false;
    }

    /**
     * Получаем замену макроса по его ключу
     * @param string $key ключ макроса
     * @param string $languageKey ключ языка
     * @return string
     */
    protected function getMacrosReplacement($key, $languageKey = LNG)
    {
        static $i, $url, $host;
        if (!$i) {
            $i = true;
            if ( ! bff::isRobot()) {
                $url = Geo::url(Geo::filter('url'), false, false); # user
            } else {
                $url = Geo::url(Geo::filterUrl(), false, false); # seo
            }
            $host = SITEHOST . $this->locale->getLanguageUrlPrefix($languageKey, false);
        }

        switch ($key) {
            case static::MACROS_SITEURL:
                return $url;
                break;
            case static::MACROS_SITEHOST:
                return $host;
                break;
        }

        return $key;
    }

}