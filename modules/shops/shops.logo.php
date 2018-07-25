<?php

class ShopsLogo_ extends CImageUploader
{
    const szMini = 'm'; # mini - внутренняя почта (переписка)
    const szSmall = 's'; # small - внутренняя почта (список)
    const szList = 'l'; # list - список, форма
    const szView = 'v'; # view - просмотр

    function initSettings()
    {
        $this->path = bff::path('shop' . DS . 'logo', 'images');
        $this->pathTmp = bff::path('tmp', 'images');

        $this->url = bff::url('shop/logo', 'images');
        $this->urlTmp = bff::url('tmp', 'images');

        $this->table = TABLE_SHOPS;
        $this->fieldID = 'id';
        $this->fieldImage = 'logo';
        $this->filenameLetters = 6;
        $this->folderByID = true;
        $this->maxSize = config::sys('shops.logo.maxsize', 3145728, TYPE_UINT); # 3мб
        $this->minWidth  = config::sysAdmin('shops.logo.width.min', 200, TYPE_UINT);
        $this->minHeight = config::sysAdmin('shops.logo.height.min', 80, TYPE_UINT);
        $this->maxWidth  = config::sysAdmin('shops.logo.width.max', 1500, TYPE_UINT);
        $this->maxHeight = config::sysAdmin('shops.logo.height.max', 1500, TYPE_UINT);
        $this->sizes = bff::filter('shops.logo.sizes', array(
            self::szMini  => array('width' => 35, 'height' => 35),
            self::szSmall => array('width' => 65, 'height' => 65),
            self::szList  => array('width' => 200, 'height' => 80),
            self::szView  => array('width' => 218, 'height' => false),
        ));
    }

    public static function url($nShopID, $sFilename, $sSizePrefix, $bTmp = false, $bDefault = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new static();
        }
        $i->setRecordID($nShopID);

        if (empty($sFilename)) {
            return ($bDefault ? $i->urlDefault($sSizePrefix) : false);
        }

        return $i->getURL($sFilename, $sSizePrefix, $bTmp);
    }

    public function urlDefault($sSizePrefix)
    {
        return $this->url . 'def-' . $sSizePrefix . '.png';
    }

    /**
     * Обработка формы загрузки логотипа
     * @param bool $bOnCreate загрузка логитипа при создании магазина
     * @param string $sInputFile имя поля загрузки логотипа (input="file")
     * @param string $sInputDeleteCheckbox имя поля удаления логотипа (input="checkbox")
     * @return mixed
     */
    public function onSubmit($bOnCreate, $sInputFile = 'shop_logo', $sInputDeleteCheckbox = 'shop_logo_del')
    {
        if ($bOnCreate) {
            $this->setAssignErrors(false);
            $aUpload = $this->uploadFILES($sInputFile, false, false);
            if (!empty($aUpload['filename'])) {
                return $aUpload['filename'];
            }
        } else {
            $aUpload = $this->uploadFILES($sInputFile, true, false);
            if (!empty($aUpload['filename'])) {
                return $aUpload['filename'];
            } else {
                if ($this->input->postget($sInputDeleteCheckbox, TYPE_BOOL)) {
                    if ($this->delete(false)) {
                        return '';
                    }
                }
            }

            return false;
        }
    }
}