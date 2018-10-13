<?php namespace bff\extend;

/**
 * Плагинизация: настройки расширения - загрузка изображений
 * @version 0.11
 * @modified 24.jul.2017
 * @copyright Tamaranga
 */

class ExtensionImage extends \CImagesUploaderField
{
    protected function initSettings()
    {
        $this->path    = \bff::path('extensions', 'images');
        $this->pathTmp = \bff::path('extensions', 'images');
        $this->url     = \bff::url('extensions',  'images');
        $this->urlTmp  = \bff::url('extensions',  'images');

        $this->filenameLetters = 10;
        $this->maxSize = 10485760; # 10 mb
        $this->limit = 1;
    }
}