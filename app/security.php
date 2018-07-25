<?php

class Security_ extends \bff\base\Security
{
    function init()
    {
        # Путь к админ.панели, формат: "/dir/dir/", "/dir/", "/" (слэш в начале и конце пути обязателен)
        $this->sessionCookie['admin-path'] = bff::adminPanel(true);
        # Домен админ.панели, формат: "admin.example.com" или false (SITEHOST)
        $this->sessionCookie['admin-domain'] = false;

        parent::init();
    }

    function getAdminPath()
    {
        return rtrim($this->sessionCookie['admin-path'], '/');
    }

    function getUserPasswordMD5($sPassword, $sSalt = '')
    {
        if (empty($sSalt)) {
            $sSalt = $this->getUserInfo('password_salt');
        }

        return md5('&^%$^&*(hVAb][CKj9vyeyhtJR' . $sSalt . '[t5pGET37mXm6DFdc]' . $sSalt . 'L2W2U3' . md5($sPassword) . 'E5H75522rXxx2SNs6C&^%$&');
    }

    function getRememberMePasswordMD5($sPassword)
    {
        return (md5('4pwH5yMdyaKZ2r6s478__?:)*' . md5($sPassword) . ']W4rymXMNuhp6eC5C^#$['));
    }

    function getRememberMeIPAddressMD5($sExtra = '')
    {
        return md5('TYN2HnLP' . Request::remoteAddress() . 'B5jXFXWN' . $sExtra . 'G5LfFQHMT');
    }

    function getShopID()
    {
        return (isset($this->sessionData['shop_id']) ? (integer)$this->sessionData['shop_id'] : 0);
    }

    function getLang()
    {
        return (isset($this->sessionData['lang']) ? $this->sessionData['lang'] : LNG);
    }

}