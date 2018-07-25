<?php

/**
 * Используемые таблицы:
 * TABLE_SITEMAP - таблица пунктов меню
 * TABLE_SITEMAP_LANG - мультиязычность
 */

use bff\db\NestedSetsTree;

class SitemapModelBase extends Model
{
    /** @var NestedSetsTree */
    public $tree;

    public $langItems = array(
        'title'        => TYPE_STR, # Название
        'mtitle'       => TYPE_STR, # Meta title
        'mkeywords'    => TYPE_STR, # Meta keywords
        'mdescription' => TYPE_STR, # Meta description
    );

    public function init()
    {
        parent::init();

        $this->tree = new NestedSetsTree(TABLE_SITEMAP);
        $this->tree->init();
    }

    /**
     * Создаем раздел
     * @param int $nParentItemID @ref ID parent-раздела
     * @param array $aParentData данные о parent-разделе
     * @param array $aData данные
     * @return int ID созданного раздела или 0
     */
    public function itemCreate(&$nParentItemID, array $aParentData, array $aData)
    {
        $aData['created'] = $this->db->now();

        $nItemID = $this->tree->insertNode($nParentItemID);
        if (empty($nItemID)) {
            return 0;
        }

        $this->db->update(TABLE_SITEMAP, array_diff_key($aData, $this->langItems), array('id' => $nItemID));

        $aData['id'] = $nItemID;

        if ($aParentData['numlevel'] > 1) {
            $aMainParentID = $this->tree->getNodeParentsID($nParentItemID, ' AND numlevel = 1 ');
            if (!empty($aMainParentID)) {
                reset($aMainParentID);
                $nParentItemID = current($aMainParentID);
            }
        }

        $this->db->langInsert($nItemID, $aData, $this->langItems, TABLE_SITEMAP_LANG);

        return $nItemID;
    }

    /**
     * Обновляем данные о разделе
     * @param int $nItemID ID раздела
     * @param array $aData данные
     * @return bool
     */
    public function itemUpdate($nItemID, array $aData)
    {
        if (empty($nItemID) || empty($aData)) {
            return false;
        }

        $res = $this->db->update(TABLE_SITEMAP, array_diff_key($aData, $this->langItems), array('id' => $nItemID));

        $this->db->langUpdate($nItemID, $aData, $this->langItems, TABLE_SITEMAP_LANG);

        return !empty($res);
    }

    /**
     * Удаляем раздел
     * @param int $nItemID ID раздела
     * @return bool
     */
    public function itemDelete($nItemID)
    {
        $aData = $this->db->one_array('SELECT * FROM ' . TABLE_SITEMAP . ' WHERE id = :id', array(':id' => $nItemID));
        if (empty($aData)) {
            return false;
        }

        if ($aData['is_system'] && !FORDEV) {
            $this->errors->accessDenied();

            return false;
        }

        $aDeleteItemsID = $this->tree->deleteNode($nItemID);
        if (!$aDeleteItemsID) {
            return false;
        } else {
            $this->db->delete(TABLE_SITEMAP_LANG, $nItemID);

            return true;
        }
    }

    /**
     * Получаем данные о разделе
     * @param int $nItemID ID раздела
     * @return array
     */
    public function itemData($nItemID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT * FROM ' . TABLE_SITEMAP . ' WHERE id = :id', array(':id' => $nItemID));
            if (!empty($aData)) {
                $this->db->langSelect($nItemID, $aData, $this->langItems, TABLE_SITEMAP_LANG);
            }
        } else {
            $aData = $this->db->one_array('SELECT I.*, L.*
                    FROM ' . TABLE_SITEMAP . ' I,
                         ' . TABLE_SITEMAP_LANG . ' L
                    WHERE I.id = :id ' . (!FORDEV ? ' AND I.pid!=0 ' : '')
                . $this->db->langAnd(),
                array(':id' => $nItemID)
            );
        }

        return $aData;
    }

    /**
     * Получаем данные о разделе по фильтру
     * @param array $aFilter фильтр раздела
     * @return array
     */
    public function itemDataByFilter(array $aFilter = array())
    {
        $aFilter[':lang'] = $this->db->langAnd(false);
        $aFilter = $this->prepareFilter($aFilter, 'I');

        return $this->db->one_array('SELECT I.*, L.*
                FROM ' . TABLE_SITEMAP . ' I,
                     ' . TABLE_SITEMAP_LANG . ' L
                ' . $aFilter['where'] . '
                ORDER BY I.id ASC
                LIMIT 1', $aFilter['bind']
        );
    }

    /**
     * Перемещаем разделы
     */
    public function itemsRotate()
    {
        return $this->tree->rotateTablednd();
    }

    /**
     * Включаем/вылючаем раздел
     * @param int $nItemID ID раздела
     */
    public function itemToggle($nItemID)
    {
        $this->tree->toggleNodeEnabled($nItemID, true, false);
    }

    /**
     * Получаем список разделов в заданной ветке
     * @param int $nNumleft numleft
     * @param int $nNumright numright
     * @return mixed
     */
    public function itemsListing($nNumleft, $nNumright)
    {
        return $this->db->select('SELECT I.*, L.title, (I.type = :type) as menu
                                   FROM ' . TABLE_SITEMAP . ' I, ' . TABLE_SITEMAP_LANG . ' L
                                   WHERE I.pid != 0
                                     AND I.numleft > :nl
                                     AND I.numright < :nr
                                     ' . $this->db->langAnd() . '
                                   ORDER BY I.numleft',
            array(':type' => SitemapModule::typeMenu, ':nl' => $nNumleft, ':nr' => $nNumright)
        );
    }

    /**
     * Получаем список меню для построения их в админ-панели
     * @return mixed
     */
    public function itemsListingMenu()
    {
        return $this->db->select_key('SELECT I.id, L.title, I.numleft, I.numright, I.numlevel, 0 as active
                                    FROM ' . TABLE_SITEMAP . ' I, ' . TABLE_SITEMAP_LANG . ' L
                                    WHERE I.pid = :root AND I.type = :type ' .
            $this->db->langAnd() . '
                                    ORDER BY I.numleft', 'id',
            array(':root' => SitemapModule::ROOT_ID, ':type' => SitemapModule::typeMenu)
        );
    }

    /**
     * Получаем список разделов для построения меню
     * @return mixed
     */
    public function itemsMenu()
    {
        $aMenu = $this->db->select('SELECT I.id, I.pid, I.keyword, I.link, I.type, I.target, I.style, L.title, L.mtitle, L.mkeywords, L.mdescription, 0 as a
                   FROM ' . TABLE_SITEMAP . ' I, ' . TABLE_SITEMAP_LANG . ' L
                   WHERE I.pid>=1 AND I.enabled = 1 ' . $this->db->langAnd() . '
                   ORDER BY I.numleft'
        );

        return $this->db->transformRowsToTree($aMenu, 'id', 'pid', 'sub');
    }

    /**
     * Формируем список parent-элементов
     * @param int $nSelectedID ID текущего parent-раздела
     * @param int $nMaxNumlevel масимальный уровень вложенности
     * @return string
     */
    public function itemParentsOptions($nSelectedID, $nMaxNumlevel = 3)
    {
        $sParentOptions = '';

        $aItems = $this->db->select('SELECT I.id, L.title, I.numlevel
                                FROM ' . TABLE_SITEMAP . ' I,
                                     ' . TABLE_SITEMAP_LANG . ' L
                                WHERE (I.type = ' . SitemapModule::typeMenu . ' OR I.id = 1) AND I.numlevel < :nl
                                  ' . $this->db->langAnd() . '
                                ORDER BY I.numleft', array(':nl' => $nMaxNumlevel)
        );
        foreach ($aItems as $v) {
            $sParentOptions .= '<option value="' . $v['id'] . '"
                               ' . ($nSelectedID == $v['id'] ? ' selected' : '') . '>' . str_repeat('&nbsp', (!FORDEV ? $v['numlevel'] - 1 : $v['numlevel']) * 2) . $v['title'] . '</option>';
        }

        return $sParentOptions;
    }

    /**
     * Формируем путь parent-элементов для отображения
     * @param int $nItemID ID раздела
     * @oaram string $sSeparator разделитель
     * @return string
     */
    public function itemParentsPath($nItemID, $sSeparator = ' > ')
    {
        $aParentsID = $this->tree->getNodeParentsID($nItemID, ($nItemID == SitemapModule::ROOT_ID ? '' : ' AND numlevel > 0'), true);
        if (!empty($aParentsID)) {
            $aParentTitle = $this->db->select_one_column('SELECT title
                       FROM ' . TABLE_SITEMAP_LANG . '
                       WHERE id IN (' . join(',', $aParentsID) . ') AND lang = :lng
                       ORDER BY id', array(':lng' => LNG)
            );

            return join($sSeparator, $aParentTitle);
        }

        return '';
    }

    /**
     * Выполняем очистку таблиц модуля, оставляем только корневой раздел
     * @param string $sRootTitle название корневого раздела
     * @return bool|int
     */
    public function itemsClear($sRootTitle = '')
    {
        if (!FORDEV) {
            return false;
        }

        # чистим таблицы Sitemap
        $this->db->exec('TRUNCATE TABLE ' . TABLE_SITEMAP);
        $this->db->exec('TRUNCATE TABLE ' . TABLE_SITEMAP_LANG);
        # сбрасываем seq для postgres
        if ($this->db->isPgSQL()) {
            $this->db->pgRestartSequence(TABLE_SITEMAP);
        }

        # создаем корневой элемент
        $nRootID = $this->db->insert(TABLE_SITEMAP, array(
                'pid'           => 0,
                'keyword'       => 'root',
                'created'       => $this->db->now(),
                'is_system'     => 1,
                'numleft'       => 1,
                'numright'      => 2,
                'numlevel'      => 0,
                'enabled'       => 1,
                'allow_submenu' => 1,
            )
        );

        if (empty($nRootID)) {
            return false;
        }

        $this->input->postm_lang($this->langItems, $aData);
        foreach ($this->locale->getLanguages() as $lng) {
            $aData['title'][$lng] = (!empty($sRootTitle) ? $sRootTitle : _t('sitemap', 'Корневой раздел'));
        }
        $this->db->langInsert($nRootID, $aData, $this->langItems, TABLE_SITEMAP_LANG);

        return $nRootID;
    }

    function getLocaleTables()
    {
        return array(
            TABLE_SITEMAP => array('type' => 'table', 'fields' => $this->langItems, 'title' => _t('sitemap', 'Карта сайта')),
        );
    }
}