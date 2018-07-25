<?php namespace bff\external;

require modification(PATH_CORE . 'external/smarty/smarty.class.php');

class Smarty extends \Smarty
{
    /**
     * Делаем возможным только один экземпляр класса
     * @return \CSmarty
     */
    public static function i()
    {
        static $oInstance;
        if (isset($oInstance)) {
            return $oInstance;
        } else {
            $oInstance = new static();

            return $oInstance;
        }
    }

}