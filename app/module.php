<?php

class Module_ extends \bff\base\Module
{
    /**
     * Сокращение для доступа к модулю BBS
     * @return BBS module
     */
    function bbs()
    {
        return bff::module('bbs');
    }

    /**
     * Сокращение для доступа к модулю Shops
     * @return Shops module
     */
    public function shops()
    {
        return bff::module('shops');
    }

    /**
     * Отображаем краткую страницу с текстом (frontend)
     * @param string $sTitle заголовок страницы
     * @param string $sContent контент страницы
     * @return string HTML
     */
    public function showShortPage($sTitle, $sContent)
    {
        View::setLayout('short');
        $aData = array(
            'title'   => $sTitle,
            'content' => $sContent,
        );

        return View::template('short.page', $aData);
    }

    /**
     * Отображаем уведомление "Успешно..." (frontend)
     * @param string $sTitle заголовок сообщения
     * @param string $sMessage текст сообщения
     * @return string HTML
     */
    public function showSuccess($sTitle = '', $sMessage = '')
    {
        $aData = array(
            'message' => $sMessage,
        );

        return $this->showShortPage($sTitle, View::template('message.success', $aData));
    }

    /**
     * Отображаем уведомление об "Ошибке..." (frontend)
     * @param string $sTitle заголовок сообщения
     * @param string|integer $mMessage текст сообщения или ID сообщения (константа Errors)
     * @param bool $bAuth требуется авторизация
     * @return string HTML
     */
    public function showForbidden($sTitle = '', $mMessage = '', $bAuth = false)
    {
        $aData = array(
            'message' => (is_integer($mMessage) ? $this->errors->getSystemMessage($mMessage) : $mMessage),
            'auth'    => $bAuth,
        );

        return $this->showShortPage($sTitle, View::template('message.forbidden', $aData));
    }

    /**
     * Отображаем уведомление об "Ошибке..." в текущий layout (frontend)
     * @param string|array|integer $message текст сообщения или ID сообщения (константа Errors)
     * @return string HTML
     */
    public function showInlineMessage($message = '', array $options = array())
    {
        $aData = $options;
        $aData['message'] = (is_string($message) ? $message :
            (is_array($message) ? join('', $message) :
                (is_integer($message) ? $this->errors->getSystemMessage($message) : '')
            )
        );
        if (!empty($aData['mtitle'])) {
            bff::setMeta($aData['mtitle']);
        }

        return View::template('message.inline', $aData);
    }
}