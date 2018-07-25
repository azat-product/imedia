<?php

class Banners_ extends BannersBase
{
    /**
     * Код баннера по ключу позиции
     * @param string $sPositionKey ключ позиции
     * @param array $aSettings доп. параметры
     * @return string HTML
     */
    public static function view($sPositionKey, array $aSettings = array())
    {
        return static::i()->viewByPosition($sPositionKey, $aSettings);
    }

    /**
     * Переход по ссылке (клик на баннер)
     */
    public function click()
    {
        do {
            $nBannerID = $this->input->get('id', TYPE_UINT);
            if (!$nBannerID) {
                break;
            }

            $aData = $this->model->bannerData($nBannerID);
            if (empty($aData)) {
                break;
            }

            # +1 к кликам
            $this->model->bannerIncrement($nBannerID, 'clicks');

            $this->redirect($aData['click_url']);
        } while (false);

        $this->redirect(static::urlBase());
    }

    /**
     * Показ баннера
     */
    public function show()
    {
        $nBannerID = $this->input->get('id', TYPE_UINT);
        $sFilePath = '';

        do {
            if (!$nBannerID) {
                break;
            }
            $aData = $this->model->bannerData($nBannerID);
            if (empty($aData)) {
                $nBannerID = 0;
                break;
            }

            if (in_array($aData['type'], array(self::TYPE_CODE, self::TYPE_TEASER))) {
                $sFilePath = $this->defaultImagePath;
            } else {
                if (!empty($aData['img'])) {
                    $sFilePath = $this->buildPath($nBannerID, $aData['img'], self::szView);
                    if (!file_exists($sFilePath)) {
                        $sFilePath = $this->buildPath($nBannerID, $aData['img'], self::szThumbnail);
                        if (!file_exists($sFilePath)) {
                            $sFilePath = $this->defaultImagePath;
                        }
                    }
                }
            }
        } while (false);

        if (empty($sFilePath)) {
            $sFilePath = $this->defaultImagePath;
        }
        $res = getimagesize($sFilePath);
        header("Expires: Fri, 29 Jun 2000 14:30:00 GMT");
        header("Last-Modified: " . date("r", filectime($sFilePath))); # always modified
        header('Content-type: ' . $res['mime']);
        readfile($sFilePath);

        if ($nBannerID > 0) {
            # +1 к показам
            $this->model->bannerIncrement($nBannerID, 'shows');
        }
        exit;
    }

    /**
     * Крон задача, для актуализации баннеров
     * Рекомендуемый период: "раз в час"
     */
    public function cron()
    {
        if (!bff::cron()) {
            return;
        }

        $this->model->bannersCron();
    }

    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'cron' => array('period' => '0 * * * *'),
        );
    }

}