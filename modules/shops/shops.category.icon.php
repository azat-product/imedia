<?php

class ShopsCategoryIcon_ extends CImageUploader
{
    # варианты иконок
    const BIG = 'b'; # большая
    const SMALL = 's'; # малая

    # ключи размеров
    const szOriginal = 'o'; # оригинальный размер

    function initSettings()
    {
        $this->path = bff::path('shop' . DIRECTORY_SEPARATOR . 'cats', 'images');
        $this->pathTmp = bff::path('tmp', 'images');
        $this->url = bff::url('shop/cats', 'images');
        $this->urlTmp = bff::url('tmp', 'images');

        $this->table = TABLE_SHOPS_CATEGORIES;
        $this->fieldID = 'id';
        $this->filenameLetters = 4;
        $aVariants = $this->getVariants();
        if (!empty($aVariants)) {
            $this->setVariant(key($aVariants));
        }
    }

    function url($nCategoryID, $sFilename, $sVariantKey = self::BIG)
    {
        $this->setRecordID($nCategoryID);
        if (empty($sFilename)) {
            # иконка-заглушка
            return $this->url . 'default-' . $sVariantKey . '.png';
        } else {
            return $this->getURL($sFilename, self::szOriginal);
        }
    }

    function getVariants()
    {
        return bff::filter('shops.category.icon.variants', array(
            'icon_' . self::BIG   => array(
                'title' => _t('shops', 'Иконка (большая)'),
                'key'   => self::BIG,
                'sizes' => array(
                    self::szOriginal => array('width' => 54, 'height' => 54, 'o' => true),
                ),
            ),
            'icon_' . self::SMALL => array(
                'title' => _t('shops', 'Иконка (малая)'),
                'key'   => self::SMALL,
                'sizes' => array(
                    self::szOriginal => array('width' => 32, 'height' => 32, 'o' => true),
                ),
            ),
        ));
    }

    function setVariant($sKey)
    {
        $aVariants = $this->getVariants();
        if (isset($aVariants[$sKey])) {
            $this->fieldImage = $sKey;
            $this->sizes = $aVariants[$sKey]['sizes'];
        }
    }
}