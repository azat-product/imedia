<?php

# Таблицы
define('TABLE_BANNERS',      DB_PREFIX . 'banners'); # баннеры
define('TABLE_BANNERS_POS',  DB_PREFIX . 'banners_pos'); # позиции баннеров
define('TABLE_BANNERS_STAT', DB_PREFIX . 'banners_stat'); # статистика по баннерам

class BannersModel_ extends Model
{
    /** @var string префикс ключа кеширования */
    private $cacheKey = 'banners';

    /**
     * Инициализация объекта кеширования
     * @return Cache
     */
    public function cache()
    {
        static $cache;
        if (!isset($cache)) {
            $cache = Cache::singleton('banners', 'file');
        }

        return $cache;
    }

    /**
     * Инвалидация кеша баннеров
     * @param integer $nBannerID ID баннера или 0
     */
    public function cacheReset($nBannerID = 0)
    {
        $this->cache()->flush($this->cacheKey);
    }

    /**
     * Получаем данные о баннере
     * @param integer $nBannerID ID баннера
     * @return array
     */
    public function bannerData($nBannerID)
    {
        if (!bff::adminPanel()) {
            return $this->bannersData(array('id' => $nBannerID));
        } else {
            return $this->db->one_array('SELECT * FROM ' . TABLE_BANNERS . ' WHERE id=:id', array(':id' => $nBannerID));
        }
    }

    /**
     * Получаем данные о баннерах по ID позиции (frontend)
     * @param array $aFilter фильтр
     * @return array
     */
    public function bannersData(array $aFilter = array())
    {
        if (($aData = $this->cache()->get($this->cacheKey)) === false) {
            $aData = $this->db->select_key('SELECT * FROM ' . TABLE_BANNERS . '
                WHERE enabled = 1 AND show_start <= :start
                ORDER BY pos, num', 'id', array(
                    ':start' => date('Y-m-d 00:00')
                )
            );

            foreach ($aData as &$v) {
                # данные о регионе
                if ($v['region_id']) {
                    $v['region'] = Geo::regionData($v['region_id']);
                }
                # данные о разделах
                $v['sitemap'] = (!empty($v['sitemap_id']) ? explode(',', $v['sitemap_id']) : array());
                if (!empty($v['sitemap'])) {
                    $v['sitemap_all'] = in_array(Banners::SITEMAP_ALL, $v['sitemap']);
                    $v['sitemap_index'] = in_array(Banners::SITEMAP_INDEX, $v['sitemap']);
                }
                # данные о категориях
                $v['category'] = (!empty($v['category_id']) ? explode(',', $v['category_id']) : array());
                # локализации
                $v['locale'] = (!empty($v['locale']) ? explode(',', $v['locale']) : array());

            }
            unset($v);

            $this->cache()->set($this->cacheKey, $aData);
        }

        if (!empty($aFilter['pos'])) {
            $nPositionID = $aFilter['pos'];
            $aBanners = array();
            foreach ($aData as $k => $v) {
                if ($v['pos'] == $nPositionID) {
                    $aBanners[$k] = $v;
                }
            }

            return $aBanners;
        } else {
            if (!empty($aFilter['id'])) {
                return (isset($aData[$aFilter['id']]) ? $aData[$aFilter['id']] : array());
            } else {
                return $aData;
            }
        }
    }

    /**
     * Список баннеров (admin)
     * @param array $aFilter фильтр списка баннеров
     * @return mixed
     */
    public function bannersListing(array $aFilter = array())
    {
        $aFilter = $this->prepareFilter($aFilter, 'B');

        return $this->db->select('SELECT B.*, SUM(S.shows) as shows, SUM(S.clicks) as clicks
                               FROM ' . TABLE_BANNERS . ' B
                                 INNER JOIN ' . TABLE_BANNERS_POS . ' P ON B.pos = P.id
                                 LEFT JOIN ' . TABLE_BANNERS_STAT . ' S ON S.banner_id = B.id
                               ' . $aFilter['where'] . '
                               GROUP BY B.id',
            $aFilter['bind']
        );
    }

    /**
     * Сохранение/обновление баннера
     * @param integer $nBannerID ID баннера
     * @param array $aData данные баннера
     * @return mixed
     */
    public function bannerSave($nBannerID, array $aData = array())
    {
        if ($nBannerID) {
            $res = $this->db->update(TABLE_BANNERS, $aData, array('id' => $nBannerID));

            return !empty($res);
        } else {
            $aData['created'] = $this->db->now();
            if( ! isset($aData['num'])){
                $aData['num'] = (int)$this->db->one_data('SELECT MAX(num) FROM '.TABLE_BANNERS) + 1;
            }
            return $this->db->insert(TABLE_BANNERS, $aData, 'id');
        }
    }

    /**
     * Удаление баннера
     * @param integer $nBannerID ID баннера
     */
    public function bannerDelete($nBannerID)
    {
        $this->db->delete(TABLE_BANNERS, array('id' => $nBannerID));
        $this->db->delete(TABLE_BANNERS_STAT, array('banner_id' => $nBannerID));
        $this->cacheReset($nBannerID);
    }

    /**
     * Получаем ID баннеров по ID позиции
     * @param integer $nPositionID ID позиции
     * @return array ID баннеров связанных с указанной позицией
     */
    public function bannersByPosition($nPositionID)
    {
        return $this->db->select_one_column('SELECT id FROM ' . TABLE_BANNERS . ' WHERE pos = :pos', array(':pos' => $nPositionID));
    }

    /**
     * Связываем баннеры с указанной позицией {$nPositionID}
     * @param array $aBannersID ID баннеров
     * @param integer $nPositionID ID позиции
     * @return integer кол-во перемещенных баннеров
     */
    public function bannersToPosition(array $aBannersID, $nPositionID)
    {
        if (!empty($aBannersID) && $nPositionID > 0) {
            return $this->db->update(TABLE_BANNERS, array('pos' => $nPositionID), array('id' => $aBannersID));
        }

        return 0;
    }

    /**
     * Накручиваем счетчик просмотров/переходов баннера
     * @param integer $nBannerID ID баннера
     * @param string $sField поле: 'shows', 'clicks'
     */
    public function bannerIncrement($nBannerID, $sField = 'shows')
    {
        if (!in_array($sField, array('shows', 'clicks'))) {
            return;
        }

        # +1 к показам/кликам  (MySQL ONLY)
        $this->db->exec('INSERT INTO ' . TABLE_BANNERS_STAT . ' (banner_id, ' . $sField . ', period)
                      VALUES (:id, 1, :period)
                      ON DUPLICATE KEY UPDATE ' . $sField . ' = ' . $sField . ' + 1',
            array(':id' => $nBannerID, ':period' => date('Y-m-d'))
        );
    }

    /**
     * Получаем ID следующего по счету баннера
     * @return int
     */
    public function bannerNextID()
    {
        $nID = (int)$this->db->one_data('SELECT MAX(id) FROM ' . TABLE_BANNERS);

        return ($nID + 1);
    }

    /**
     * Актуализация баннеров по дате/лимиту показов
     */
    public function bannersCron()
    {
        # выключаем просроченные баннеры
        $bResetCache = $this->db->update(TABLE_BANNERS,
            array('enabled' => 0),
            array('enabled' => 1, 'show_finish <= :now'),
            array(':now' => $this->db->now())
        );

        # выключаем баннеры с превышенным лимитом показов
        $aBanners = $this->db->select_key('SELECT B.id, B.show_limit
                                     FROM ' . TABLE_BANNERS . ' B
                                       LEFT JOIN ' . TABLE_BANNERS_STAT . ' S ON B.id = S.banner_id
                                     WHERE B.enabled = 1 AND B.show_limit > 0
                                     GROUP BY B.id
                                     HAVING B.show_limit <= SUM(S.shows)',
            'id'
        );
        if (!empty($aBanners)) {
            $res = $this->db->update(TABLE_BANNERS,
                array('enabled' => 0),
                array('id' => array_keys($aBanners))
            );

            if (!empty($res)) {
                $bResetCache = true;
            }
        }

        if (!empty($bResetCache)) {
            $this->cacheReset();
        }
    }

    /**
     * Подробная статистика просмотров/переходов (admin)
     * @param array $aFilter фильтр списка баннеров
     * @param boolean $bCount только подсчет кол-ва
     * @param string $sqlOrder
     * @param string $sqlLimit
     * @return mixed
     */
    public function bannerStatisticListing(array $aFilter = array(), $bCount = false, $sqlOrder = '', $sqlLimit = '')
    {
        $aFilter = $this->prepareFilter($aFilter);
        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(banner_id) FROM ' . TABLE_BANNERS_STAT . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT *, ROUND(( clicks / ( (CASE WHEN shows > 0 THEN shows ELSE 1 END) ) ) * 100, 2) as ctr
                               FROM ' . TABLE_BANNERS_STAT .
            $aFilter['where'] .
            (!empty($sqlOrder) ? 'ORDER BY ' . $sqlOrder : '')
            . $sqlLimit,
            $aFilter['bind']
        );
    }

    /**
     * Перемещение баннера
     * @param integer $pos позиция
     * @return mixed @see rotateTablednd
     */
    public function bannersRotate($pos)
    {
        $this->cacheReset();
        return $this->db->rotateTablednd(TABLE_BANNERS, ' AND pos = ' . $pos);
    }

    # --------------------------------------------------------------------
    # позиции

    /**
     * Список позиций
     * @param array $aFilter фильтр списка позиций
     * @return mixed
     */
    public function positionsList(array $aFilter = array())
    {
        $aFilter = $this->prepareFilter($aFilter, 'P');
        $aData = $this->db->select_key('SELECT P.*, COUNT(B.id) as banners
               FROM ' . TABLE_BANNERS_POS . ' P
                 LEFT JOIN ' . TABLE_BANNERS . ' B ON B.pos = P.id
               ' . $aFilter['where']
            . ' GROUP BY P.id '
            . ' ORDER BY P.title ASC',
            'id',
            $aFilter['bind']
        );
        if (!empty($aData)) {
            foreach ($aData as $k => $v) {
                $aData[$k]['sizes'] = ($v['width'] > 0 ? $v['width'] : '100%') . ' x ' . ($v['height'] > 0 ? $v['height'] : '100%');
            }
        }

        return $aData;
    }

    /**
     * Получение данных позиции
     * @param integer $nPositionID ID позиции
     * @return array
     */
    public function positionData($nPositionID)
    {
        return $this->db->one_array('SELECT P.*, COUNT(B.id) as banners, SUM(B.enabled) as banners_enabled
                FROM ' . TABLE_BANNERS_POS . ' P
                    LEFT JOIN ' . TABLE_BANNERS . ' B ON B.pos = P.id
                WHERE P.id = :id
                GROUP BY P.id',
            array(':id' => $nPositionID)
        );
    }

    /**
     * Сохранение позиции
     * @param integer $nPositionID ID позиции
     * @param array $aData данные позиции
     * @return boolean|integer
     */
    public function positionSave($nPositionID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nPositionID > 0) {
            $res = $this->db->update(TABLE_BANNERS_POS, $aData, array('id' => $nPositionID));

            return !empty($res);
        } else {

            $nPositionID = $this->db->insert(TABLE_BANNERS_POS, $aData, 'id');
            if ($nPositionID > 0) {
                //
            }

            return $nPositionID;
        }
    }

    /**
     * Переключатели позиции
     * @param integer $nPositionID ID позиции
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function positionToggle($nPositionID, $sField)
    {
        switch ($sField) {
        case 'enabled':
        { # Включен
            return $this->toggleInt(TABLE_BANNERS_POS, $nPositionID, $sField, 'id');
        }
            break;
        case 'rotation':
        { # Ротация
            return $this->toggleInt(TABLE_BANNERS_POS, $nPositionID, $sField, 'id');
        }
            break;
        }
    }

    /**
     * Удаление позиции
     * @param integer $nPositionID ID позиции
     * @return boolean
     */
    public function positionDelete($nPositionID)
    {
        if (empty($nPositionID)) {
            return false;
        }
        $res = $this->db->delete(TABLE_BANNERS_POS, array('id' => $nPositionID));
        if (!empty($res)) {
            return true;
        }

        return false;
    }

    /**
     * Проверка / формирование ключа позиции
     * @param string $sPositionKeyword ключ позиции
     * @param string $sPositionTitle название позиции
     * @param integer|null $nPositionID ID позиции или NULL
     * @return string корректный ключ позиции
     */
    public function positionKeywordValidate($sPositionKeyword, $sPositionTitle, $nPositionID = null)
    {
        return $this->db->getKeyword($sPositionKeyword, $sPositionTitle, TABLE_BANNERS_POS, $nPositionID);
    }
}