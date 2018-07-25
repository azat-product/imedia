<?php

class SitemapModule extends SitemapModuleBase
{
    /** @var array|bool Загруженные данные о разделах меню */
    protected $menu = false;
    /** @var bool пункт меню уже активирован */
    protected $activated = false;
    /** @var bool данные об активном пункте меню */
    protected $activatedData = array(
        'path'    => array(), # путь к разделу/пункту меню
        'main'    => array(), # основной раздел/пункт меню
        'parent'  => array(), # parent раздел/пункт меню
        'current' => array(), # активный раздел/пункт меню
        'way'     => '', # способ активации пункта меню
    );

    /**
     * Формируем меню для вывода
     * @param bool $bFindActive подсветить активный пункт меню
     * @param string|bool $sDefaultPath пункт меню по-умолчанию ("Главная") или FALSE
     * @return array
     */
    public function buildMenu($bFindActive = true, $sDefaultPath = '/main/index')
    {
        $this->initMenu();

        if ($bFindActive && !$this->activated) {
            # подготавливаем URL, без query (?x=1)
            $sURL = Request::url();
            $aURI = parse_url(Request::uri());
            if (!empty($aURI['path'])) {
                $sURL .= $aURI['path'];
            }

            if (bff::isIndex() || $sURL == SITEURL) {
                # активируем пункт меню по-умолчанию ("Главная")
                if (!empty($sDefaultPath)) {
                    $this->setActiveMenuByPath($sDefaultPath);
                }
            } else {
                # активируем пункт меню по URL
                $this->setActiveMenuByURL($sURL);
            }
        }

        return $this->menu;
    }

    /**
     * Активируем требуемый пункт меню используя путь
     * @param string $sPath путь например: /main/items
     * @param mixed $mActiveStateData данные, которые необходимо записать в активный пункт меню (['a']=>$mActiveData)
     * @param bool $bUpdateMeta true => обновлять мета, false => возвращать мета (для дополнения)
     * @return bool
     */
    public function setActiveMenuByPath($sPath, $mActiveStateData = 1, $bUpdateMeta = true)
    {
        # проверяем путь
        if (empty($sPath)) {
            return false;
        }
        $sPath = trim($sPath, '/ ');
        if (empty($sPath)) {
            return false;
        }
        $sPath = explode('/', $sPath);
        if (empty($sPath)) {
            return false;
        }

        $this->initMenu();

        $menu =& $this->menu;
        $menuParent = array();
        $i = sizeof($sPath);
        foreach ($sPath as $key) {
            if (!isset($menu[$key])) {
                return false;
            }
            if (--$i) {
                $menuParent =& $menu[$key];
                $menu =& $menu[$key]['sub'];
                continue;
            } else {
                $menuParent['a'] = $mActiveStateData;
                $menu[$key]['a'] = $mActiveStateData;

                $this->setActivated(true, $menu[$key], 'path');

                if ($bUpdateMeta) {
                    # обновляем, но не перекрываем, если уже установлен окончательный вариант
                    if ($this->_useMetaSettings) {
                        bff::setMeta($menu[$key]['mtitle'], $menu[$key]['mkeywords'], $menu[$key]['mdescription'], array(), false);
                    }
                } else {
                    return array('mtitle'       => $menu[$key]['mtitle'],
                                 'mkeywords'    => $menu[$key]['mkeywords'],
                                 'mdescription' => $menu[$key]['mdescription']
                    );
                }

//                $this->activatedData['path'] = join('/', $sPath);
//                $this->activatedData['main'] =& $this->menu[$sPath[0]];
//                $this->activatedData['parent'] =& $menuParent;

                return true;
            }
        }

        return false;
    }

    /**
     * Активируем пункт меню по текущему URL
     * @param string $sURL например: http://example.com/page.html
     * @param mixed $mActiveStateData данные, которые необходимо записать в активный пункт меню (['a']=$mActiveData)
     * @param bool $bUpdateMeta обновлять мета
     * @return bool
     */
    public function setActiveMenuByURL($sURL, $mActiveStateData = 1, $bUpdateMeta = true)
    {
        if ($this->activated) {
            return false;
        }

        $this->initMenu();

        $this->findActiveByURL($this->menu, $sURL, $mActiveStateData, $bUpdateMeta);

        return $this->activated;
    }

    /**
     * Помечаем заверешение активации пункта меню
     * @param bool $bActivated активирован
     * @param array $aActiveData @ref данные об активированном меню
     * @param string $sActivatedWay способ активации пукнта меню
     * @param bool $bUpdateMeta обновлять мета
     */
    public function setActivated($bActivated = true, &$aActiveData = array(), $sActivatedWay = '')
    {
        $this->activated = $bActivated;
        $this->activatedData['way'] = $sActivatedWay;
        if ($bActivated) {
            $this->activatedData['current'] =& $aActiveData;
        } else {
            $this->activatedData['current'] = array();
        }
    }

    /**
     * Возвращаем данные об активном пункте меню
     * @return array:
     *   'path' => путь к активному пункту меню
     *   'main' => основной раздел меню
     *   'parent' => parent-раздел
     *   'current' => текущий активный пункт меню
     */
    public function getActivated()
    {
        return $this->activatedData;
    }

    /**
     * Возвращаем ID текущего активного пункта меню
     * @return integer
     */
    public function getActivatedID()
    {
        $aData = $this->getActivated();

        return (!empty($aData['current']['id']) ? (int)$aData['current']['id'] : 0);
    }

    /**
     * Дополняем пункт меню дополнительными данными
     * @param string $sPath путь, например: /main/items
     * @param string $sDataKey ключ по которому добавляемые данные будут доступны
     * @param mixed $aData дополнительные данные
     */
    public function setMenuDataByPath($sPath, $sDataKey, $aData)
    {
        # проверяем ключ
        if (empty($sDataKey) || in_array($sDataKey, array(
                    'id',
                    'pid',
                    'keyword',
                    'link',
                    'type',
                    'style',
                    'title',
                    'mtitle',
                    'mkeywords',
                    'mdescription',
                    'a',
                    'sub'
                )
            )
        ) {
            return false;
        }

        $this->initMenu();

        # проверяем путь
        if (empty($sPath)) {
            return false;
        }
        $sPath = trim($sPath, '/ ');
        if (empty($sPath)) {
            return false;
        }
        $sPath = explode('/', $sPath);
        if (empty($sPath)) {
            return false;
        }

        $menu =& $this->menu;
        $i = sizeof($sPath);
        foreach ($sPath as $key) {
            if (!isset($menu[$key])) {
                return false;
            }
            if (--$i) {
                $menu =& $menu[$key]['sub'];
                continue;
            } else {
                $menu[$key][$sDataKey] = $aData;

                return true;
            }
        }

        return false;
    }

    /**
     * Удаляем пункт меню по указанному пути
     * @param string $sPath путь, например: /main/items
     * @return bool
     */
    protected function removeMenuByPath($sPath)
    {
        $this->initMenu();

        # проверяем путь
        if (empty($sPath)) {
            return false;
        }
        $sPath = trim($sPath, '/ ');
        if (empty($sPath)) {
            return false;
        }
        $sPath = explode('/', $sPath);
        if (empty($sPath)) {
            return false;
        }

        $menu =& $this->menu;
        $i = sizeof($sPath);
        foreach ($sPath as $key) {
            if (!isset($menu[$key])) {
                return false;
            }
            if (--$i) {
                $menu =& $menu[$key]['sub'];
                continue;
            } else {
                unset($menu[$key]);

                return true;
            }
        }

        return false;
    }

    /**
     * Формируем меню (кешируем)
     */
    protected function initMenu()
    {
        if ($this->menu !== false) {
            return;
        }

        $cache = $this->initCache();
        $cacheKey = 'menu-' . LNG;
        if (($this->menu = $cache->get($cacheKey)) === false) {
            $aMenu = $this->model->itemsMenu();
            $aMenu = $this->prepareMenu($aMenu);
            $cache->set($cacheKey, ($this->menu = $aMenu));
        }
        if (!$this->_replaceMacrosBeforeCache) {
            $this->replaceMacrosRecursive($this->menu, LNG);
        }
    }

    /**
     * Активируем пункт меню по URL
     * @param array $aMenu @ref разделы/пункты меню
     * @param string $sURL например: http://example.com/page.html
     * @param mixed $mActiveStateData данные, которые необходимо записать в активный пункт меню (['a']=$mActiveData)
     * @param bool $bUpdateMeta обновлять мета
     * @return bool
     */
    protected function findActiveByURL(&$aMenu, $sURL, $mActiveStateData = 1, $bUpdateMeta = true)
    {
        foreach ($aMenu as $k => $v) {
            if ($v['type'] == self::typeMenu) {
                if (!empty($v['sub'])) {
                    if ($this->findActiveByURL($v['sub'], $sURL, $mActiveStateData, $bUpdateMeta)) {
                        $aMenu[$k]['sub'] = $v['sub'];

                        return true;
                    }
                }
            } else {
                if (!empty($v['link']) && $v['link'] == $sURL) {
                    $aMenu[$k]['a'] = $mActiveStateData;
                    $this->setActivated(true, $v, 'url');
                    if ($bUpdateMeta && $this->_useMetaSettings) {
                        # обновляем, но не перекрываем, если уже установлены
                        bff::setMeta($v['mtitle'], $v['mkeywords'], $v['mdescription'], array(), false);
                    }

                    return true;
                }
            }
        }

        return false;
    }
}