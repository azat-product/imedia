<?php

define('TABLE_BLOG_POSTS',           DB_PREFIX . 'blog_posts');
define('TABLE_BLOG_POSTS_LANG',      DB_PREFIX . 'blog_posts_lang');
define('TABLE_BLOG_TAGS',            DB_PREFIX . 'blog_tags');
define('TABLE_BLOG_POSTS_TAGS',      DB_PREFIX . 'blog_posts_tags');
define('TABLE_BLOG_CATEGORIES',      DB_PREFIX . 'blog_categories');
define('TABLE_BLOG_CATEGORIES_LANG', DB_PREFIX . 'blog_categories_lang');

class BlogModel_ extends Model
{
    /** @var BlogBase */
    var $controller;

    public $langPosts = array(
        'title'          => TYPE_STR, # Заголовок
        'textshort'      => TYPE_STR, # Краткое описание
        'mtitle'         => TYPE_NOTAGS, # Meta Title
        'mkeywords'      => TYPE_NOTAGS, # Meta Keywords
        'mdescription'   => TYPE_NOTAGS, # Meta Description
        'share_title'    => TYPE_NOTAGS, # Meta Share Title
        'share_description' => TYPE_NOTAGS, # Meta Share Description
        'share_sitename'    => TYPE_NOTAGS, # Meta Share Sitename
        'content_search' => TYPE_STR, # Содержание-поиск
    );

    /** @var bff\db\NestedSetsTree для категорий */
    public $treeCategories;
    public $langCategories = array(
        'title'        => TYPE_STR, # Название
        'mtitle'       => TYPE_NOTAGS, # Meta Title
        'mkeywords'    => TYPE_NOTAGS, # Meta Keywords
        'mdescription' => TYPE_NOTAGS, # Meta Description
    );

    public function init()
    {
        parent::init();

        # подключаем nestedSets категории
        $this->treeCategories = new bff\db\NestedSetsTree(TABLE_BLOG_CATEGORIES);
        $this->treeCategories->init();
    }

    public function categoryRootID()
    {
        return 1; # ID "Корневой категории" (изменять не рекомендуется)
    }

    # --------------------------------------------------------------------
    # Посты

    /**
     * Список постов (admin)
     * @param array $aFilter фильтр списка постов
     * @param integer $nTagID ID тега
     * @param bool $bCount только подсчет кол-ва постов
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function postsListing(array $aFilter, $nTagID, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        $aFilter[':lang'] = $this->db->langAnd(false, 'P', 'PL');
        $aFilter = $this->prepareFilter($aFilter, 'P');

        $sqlJoin = ' LEFT JOIN ' . TABLE_BLOG_CATEGORIES_LANG . ' C ON C.id = P.cat_id AND C.lang = '.$this->db->str2sql(LNG);
        if ($nTagID > 0) {
            $sqlJoin .= ' INNER JOIN ' . TABLE_BLOG_POSTS_TAGS . ' T ON T.post_id = P.id AND T.tag_id = :tag ';
            $aFilter['bind'][':tag'] = $nTagID;
        }

        if ($bCount) {
            return $this->db->tag('blog-posts-listing-count', array('filter'=>&$aFilter,'join'=>&$sqlJoin))->one_data('SELECT COUNT(P.id)
                FROM ' . TABLE_BLOG_POSTS . ' P ' . $sqlJoin . ',
                     ' . TABLE_BLOG_POSTS_LANG . ' PL
                     ' . $aFilter['where'] .
                ($nTagID > 0 ? 'GROUP BY P.id' : ''), $aFilter['bind']
            );
        }

        return $this->db->tag('blog-posts-listing-data', array('filter'=>&$aFilter,'join'=>&$sqlJoin))->select('SELECT P.id, P.link, P.created, PL.title, P.enabled, P.fav, P.cat_id, C.title as cat_title
               FROM ' . TABLE_BLOG_POSTS . ' P ' . $sqlJoin . ', ' . TABLE_BLOG_POSTS_LANG . ' PL
               ' . $aFilter['where'] .
            ' GROUP BY P.id '
            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Список постов (frontend)
     * @param array $aFilter фильтр списка постов
     * @param bool $bCount только подсчет кол-ва постов
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function postsList(array $aFilter = array(), $bCount = false, $sqlLimit = '')
    {
        $sqlJoin = '';
        $sqlOrder = 'P.created DESC';
        if (isset($aFilter['tag'])) {
            $tagID = $aFilter['tag'];
            unset($aFilter['tag']);
            $sqlJoin = ', ' . TABLE_BLOG_POSTS_TAGS . ' T ';
            $aFilter[':tag'] = array('T.post_id = P.id AND T.tag_id = :tag', ':tag' => $tagID);
        }
        if (isset($aFilter['fav'])) {
            $aFilter[':fav'] = 'P.fav > 0';
            unset($aFilter['fav']);
            $sqlOrder = 'P.fav';
        }

        $aFilter['enabled'] = 1;
        if (!$bCount) {
            $aFilter[':lang'] = $this->db->langAnd(false, 'P', 'PL');
        }
        $aFilter = $this->prepareFilter($aFilter, 'P');

        if ($bCount) {
            return $this->db->tag('blog-posts-list-count', array('filter'=>&$aFilter,'join'=>&$sqlJoin))->one_data('SELECT COUNT(P.id) FROM ' . TABLE_BLOG_POSTS . ' P ' . $sqlJoin . '
                    ' . $aFilter['where'], $aFilter['bind']
            );
        }

        $aData = $this->db->tag('blog-posts-list-data', array('filter'=>&$aFilter,'join'=>&$sqlJoin))->select('SELECT P.id, P.link, P.created, PL.title, PL.textshort, P.preview
                                  FROM ' . TABLE_BLOG_POSTS . ' P ' . $sqlJoin . ', ' . TABLE_BLOG_POSTS_LANG . ' PL
                                  ' . $aFilter['where'] . '
                                  ' . 'ORDER BY ' . $sqlOrder . ' ' . $sqlLimit, $aFilter['bind']
        );

        if (empty($aData)) {
            $aData = array();
        }

        return $aData;
    }

    /**
     * Формирование данных о страницах блога для файла Sitemap.xml
     * @param array $aFilter фильтр
     * @param string $sPriority приоритетность url
     * @return callable callback-генератор строк вида array [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function postsSitemapXmlData(array $aFilter = array(), $sPriority = '')
    {
        if (empty($aFilter)) {
            $aFilter['enabled'] = 1;
        }
        return function($count = false, callable $callback = null) use ($aFilter, $sPriority) {
            if ($count) {
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->one_data('SELECT COUNT(*) FROM '.TABLE_BLOG_POSTS.' '.$aFilter['where'], $aFilter['bind']);
            } else {
                $aFilter = $this->prepareFilter($aFilter, '', array(
                    ':format' => '%Y-%m-%d',
                ));
                $this->db->tag('blog-posts-sitemap-xml-data', array('filter'=>&$aFilter))->select_iterator('
                    SELECT link as l, DATE_FORMAT(modified, :format) as m
                    FROM '.TABLE_BLOG_POSTS.'
                    '. $aFilter['where'] .'
                    ORDER BY modified DESC',
                    $aFilter['bind'],
                    function (&$post) use (&$callback, $sPriority) {
                        $post['l'] = Blog::urlDynamic($post['l']);
                        if ( ! empty($sPriority)) {
                            $item['p'] = $sPriority;
                        }
                        $callback($post);
                    });
            }
            return false;
        };
    }

    /**
     * Просмотр поста (frontend)
     * @param integer $nPostID ID поста
     * @return array
     */
    public function postView($nPostID)
    {
        $aFields = array();
        $aData = $this->db->tag('blog-post-view-data', array('fields'=>&$aFields))->one_array('SELECT P.id, P.cat_id, P.created, P.link, PL.title, P.content,
                        PL.mtitle, PL.mkeywords, PL.mdescription, P.modified,
                        PL.share_title, PL.share_description, PL.share_sitename,
                        P.mtemplate, PL.textshort
                        '.(!empty($aFields) ? ','.join(',', $aFields) : '').'
                    FROM ' . TABLE_BLOG_POSTS . ' P,
                         ' . TABLE_BLOG_POSTS_LANG . ' PL
                    WHERE P.id = :id AND P.enabled = 1
                        ' . $this->db->langAnd(true, 'P', 'PL') . '
                    ', array(':id' => $nPostID)
        );
        if (empty($aData)) {
            return false;
        }

        return $aData;
    }

    /**
     * Данные для ссылки "следующий пост" (frontend)
     * @param integer $nPostID ID поста
     * @param string $sCreated дата создания поста
     * @return mixed
     */
    public function postNext($nPostID, $sCreated)
    {
        return $this->db->tag('blog-post-next-data')->one_array('SELECT P.id, P.link
                FROM ' . TABLE_BLOG_POSTS . ' P
                WHERE P.id != :id AND P.created > :created AND P.enabled = 1
                ORDER BY P.created ASC LIMIT 1', array(':id' => $nPostID, ':created' => $sCreated)
        );
    }

    /**
     * Получение данных поста
     * @param integer $nPostID ID поста
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function postData($nPostID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT P.*
                    FROM ' . TABLE_BLOG_POSTS . ' P
                    WHERE P.id = :id',
                array(':id' => $nPostID)
            );
            if (!empty($aData)) {
                $this->db->langSelect($nPostID, $aData, $this->langPosts, TABLE_BLOG_POSTS_LANG);
            }
        } else {
            //
        }

        return $aData;
    }

    /**
     * Сохранение поста
     * @param integer $nPostID ID поста
     * @param array $aData данные поста
     * @return boolean|integer
     */
    public function postSave($nPostID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nPostID > 0) {
            $aData['modified'] = $this->db->now();
            $res = $this->db->update(TABLE_BLOG_POSTS, array_diff_key($aData, $this->langPosts), array('id' => $nPostID));
            $this->db->langUpdate($nPostID, $aData, $this->langPosts, TABLE_BLOG_POSTS_LANG);

            return !empty($res);
        } else {
            $aData['created'] = $this->db->now();
            $aData['modified'] = $this->db->now();
            $aData['user_id'] = $this->security->getUserID();

            $nPostID = $this->db->insert(TABLE_BLOG_POSTS, array_diff_key($aData, $this->langPosts));
            if ($nPostID > 0) {
                $this->db->langInsert($nPostID, $aData, $this->langPosts, TABLE_BLOG_POSTS_LANG);
                //
            }

            return $nPostID;
        }
    }

    /**
     * Переключатели поста
     * @param integer $nPostID ID поста
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function postToggle($nPostID, $sField)
    {
        switch ($sField) {
            case 'enabled':
            {
                return $this->toggleInt(TABLE_BLOG_POSTS, $nPostID, $sField, 'id');
            }
            break;
            case 'fav':
            {
                return $this->toggleInt(TABLE_BLOG_POSTS, $nPostID, $sField, 'id', true);
            }
            break;
        }
    }

    /**
     * Изменение порядка постов
     * @param string $sOrderField поле, по которому производится сортировка
     * @param string $aCond дополнительные условия
     * @return mixed @see rotateTablednd
     */
    public function postsRotate($sOrderField, $aCond = '')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . (is_array($aCond) ? join(' AND ', $aCond) : $aCond);
        }

        return $this->db->rotateTablednd(TABLE_BLOG_POSTS, $aCond, 'id', $sOrderField);
    }

    /**
     * Перемещение постов из одной категории ($nCatOldID) в другую ($nCatNewID)
     * @param int $nCatNewID ID категории, в которую перемещаем посты
     * @param int $nCatOldID ID категории, из которой перемещаем посты
     * @return mixed
     */
    public function postsMoveToCategory($nCatNewID, $nCatOldID)
    {
        if (empty($nCatNewID) || empty($nCatOldID)) {
            return false;
        }

        # перемещаем
        return $this->db->update(TABLE_BLOG_POSTS, array('cat_id' => $nCatNewID), array('cat_id' => $nCatOldID));
    }

    /**
     * Удаление поста
     * @param integer $nPostID ID поста
     * @return boolean
     */
    public function postDelete($nPostID)
    {
        if (empty($nPostID)) {
            return false;
        }
        $res = $this->db->delete(TABLE_BLOG_POSTS, array('id' => $nPostID));
        if (!empty($res)) {
            $this->db->delete(TABLE_BLOG_POSTS_LANG, array('id' => $nPostID));

            return true;
        }

        return false;
    }

    # --------------------------------------------------------------------
    # Категории

    /**
     * Список категорий (admin)
     * @param array $aFilter фильтр списка категорий
     * @param bool $bCount только подсчет кол-ва категорий
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function categoriesListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = 'numleft')
    {
        $aFilter[':lang'] = $this->db->langAnd(false, 'C', 'CL');
        $aFilter[] = 'pid != 0';
        $aFilter = $this->prepareFilter($aFilter, 'C');

        if ($bCount) {
            return $this->db->tag('blog-categories-listing-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(C.id) FROM ' . TABLE_BLOG_CATEGORIES . ' C, ' . TABLE_BLOG_CATEGORIES_LANG . ' CL ' . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->tag('blog-categories-listing-data', array('filter'=>&$aFilter))->select('SELECT C.id, C.created, C.enabled, CL.title, C.pid, C.numlevel,
                    ((C.numright-C.numleft)-1) as node, COUNT(P.id) as posts
               FROM ' . TABLE_BLOG_CATEGORIES . ' C
                        LEFT JOIN ' . TABLE_BLOG_POSTS . ' P ON P.cat_id = C.id,
                    ' . TABLE_BLOG_CATEGORIES_LANG . ' CL
               ' . $aFilter['where'] . ' GROUP BY C.id '
            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Список категорий (frontend)
     * @return mixed
     */
    public function categoriesList()
    {
        $aFilter = $this->prepareFilter(array(
                'enabled' => 1,
                'pid != 0',
                ':lang'   => $this->db->langAnd(false, 'C', 'CL')
            ), 'C'
        );

        $aData = $this->db->tag('blog-categories-list-data', array('filter'=>&$aFilter))->select('SELECT C.id, C.keyword, CL.title, COUNT(P.id) as posts
                                  FROM ' . TABLE_BLOG_CATEGORIES . ' C
                                       LEFT JOIN ' . TABLE_BLOG_POSTS . ' P ON P.cat_id = C.id AND P.enabled = 1,
                                       ' . TABLE_BLOG_CATEGORIES_LANG . ' CL
                                  ' . $aFilter['where'] . '
                                  GROUP BY C.id
                                  ORDER BY C.numleft', $aFilter['bind']
        );

        if (empty($aData)) {
            return array();
        }

        return $aData;
    }

    /**
     * Просмотр постов в категории
     * @param string $sKeyword keyword категории
     * @return array|boolean
     */
    public function categoryView($sKeyword)
    {
        return $this->db->tag('blog-category-view-data')->one_array('SELECT C.id, CL.title, CL.mtitle, CL.mkeywords, CL.mdescription, C.mtemplate
                            FROM ' . TABLE_BLOG_CATEGORIES . ' C,
                                 ' . TABLE_BLOG_CATEGORIES_LANG . ' CL
                            WHERE C.keyword = :key AND C.enabled = 1
                                ' . $this->db->langAnd(true, 'C', 'CL') . '
                            ', array(':key' => $sKeyword)
        );
    }

    /**
     * Получение данных категории
     * @param integer $nCategoryID ID категории
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function categoryData($nCategoryID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT C.*, ((C.numright-C.numleft)-1) as node, COUNT(P.id) as posts
                    FROM ' . TABLE_BLOG_CATEGORIES . ' C
                        LEFT JOIN ' . TABLE_BLOG_POSTS . ' P ON P.cat_id = C.id
                    WHERE C.id = :id
                    GROUP BY C.id',
                array(':id' => $nCategoryID)
            );
            if (!empty($aData)) {
                $this->db->langSelect($nCategoryID, $aData, $this->langCategories, TABLE_BLOG_CATEGORIES_LANG);
            }
        } else {
            $aData = $this->db->one_array('SELECT C.*
                FROM ' . TABLE_BLOG_CATEGORIES . ' C
                WHERE C.id = :id', array(':id' => $nCategoryID)
            );
        }

        return $aData;
    }

    /**
     * Сохранение категории
     * @param integer $nCategoryID ID категории
     * @param array $aData данные категории
     * @return boolean|integer
     */
    public function categorySave($nCategoryID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nCategoryID > 0) {
            $aData['modified'] = $this->db->now();

            if (isset($aData['pid'])) {
                unset($aData['pid']); # запрет изменения pid
            }
            $res = $this->db->update(TABLE_BLOG_CATEGORIES, array_diff_key($aData, $this->langCategories), array('id' => $nCategoryID));

            $this->db->langUpdate($nCategoryID, $aData, $this->langCategories, TABLE_BLOG_CATEGORIES_LANG);

            return !empty($res);
        } else {

            $aData['created'] = $this->db->now();
            $aData['modified'] = $this->db->now();

            $nCategoryID = $this->treeCategories->insertNode($aData['pid']);
            if ($nCategoryID > 0) {
                unset($aData['pid']);
                $this->db->update(TABLE_BLOG_CATEGORIES, array_diff_key($aData, $this->langCategories), 'id = :id', array(':id' => $nCategoryID));
                $this->db->langInsert($nCategoryID, $aData, $this->langCategories, TABLE_BLOG_CATEGORIES_LANG);
            }

            return $nCategoryID;
        }
    }

    /**
     * Переключатели категории
     * @param integer $nCategoryID ID категории
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function categoryToggle($nCategoryID, $sField)
    {
        switch ($sField) {
            case 'enabled':
                return $this->toggleInt(TABLE_BLOG_CATEGORIES, $nCategoryID, $sField, 'id');
                break;
        }
    }

    /**
     * Перемещение категории
     * @return mixed @see rotateTablednd
     */
    public function categoriesRotate()
    {
        return $this->treeCategories->rotateTablednd();
    }

    /**
     * Удаление категории
     * @param integer $nCategoryID ID категории
     * @return boolean
     */
    public function categoryDelete($nCategoryID)
    {
        if (empty($nCategoryID)) {
            return false;
        }

        $nPosts = $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BLOG_POSTS . ' I WHERE I.cat_id = :id', array(':id' => $nCategoryID));
        if (!empty($nPosts)) {
            $this->errors->set(_t('blog','Невозможно выполнить удаление категории при наличии постов'));

            return false;
        }

        $aDeletedID = $this->treeCategories->deleteNode($nCategoryID);
        $res = !empty($aDeletedID);
        if (!empty($res)) {
            $this->db->delete(TABLE_BLOG_CATEGORIES_LANG, array('id' => $nCategoryID));

            return true;
        }

        return false;
    }

    /**
     * Удаление всех категорий
     */
    public function categoriesDeleteAll()
    {
        # чистим таблицу категорий (+ зависимости по внешним ключам)
        $this->db->exec('DELETE FROM ' . TABLE_BLOG_CATEGORIES . ' WHERE id > 0');
        $this->db->exec('TRUNCATE TABLE ' . TABLE_BLOG_CATEGORIES_LANG);
        $this->db->exec('ALTER TABLE ' . TABLE_BLOG_CATEGORIES . ' AUTO_INCREMENT = 2');
        $this->db->update(TABLE_BLOG_POSTS, array('cat_id' => 0));

        # создаем корневую директорию
        $nRootID = $this->categoryRootID();
        $sRootTitle = _t('','Корневой раздел');
        $aData = array(
            'id'       => $nRootID,
            'pid'      => 0,
            'numleft'  => 1,
            'numright' => 2,
            'numlevel' => 0,
            'keyword'  => 'root',
            'enabled'  => 1,
            'created'  => $this->db->now(),
            'modified' => $this->db->now(),
        );
        $res = $this->db->insert(TABLE_BLOG_CATEGORIES, $aData);
        if (!empty($res)) {
            $aDataLang = array('title' => array());
            foreach ($this->locale->getLanguages() as $lng) {
                $aDataLang['title'][$lng] = $sRootTitle;
            }
            $this->db->langInsert($nRootID, $aDataLang, $this->langCategories, TABLE_BLOG_CATEGORIES_LANG);
        }

        return !empty($res);
    }

    /**
     * Формирование списка основных категорий
     * @param integer $nSelectedID ID выбранной категории
     * @param mixed $mEmptyOpt невыбранное значение
     * @param integer $nType тип списка: 0 - все(кроме корневого), 1 - список при добавлении категории, 2 - список при добавлении записи
     * @param array $aOnlyID только список определенных категорий
     * @return string <option></option>...
     */
    public function categoriesOptions($nSelectedID = 0, $mEmptyOpt = false, $nType = 0, $aOnlyID = array())
    {
        $aFilter = array();
        if ($nType == 1) {
            $aFilter[] = 'numlevel < 1';
        } else {
            $aFilter[] = 'numlevel > 0';
        }
        if (!empty($aOnlyID)) {
            $aFilter[':only'] = '(C.id IN (' . join(',', $aOnlyID) . ') OR C.pid IN(' . join(',', $aOnlyID) . '))';
        }

        // Chrome не понимает style="padding" в option
        $bUsePadding = (mb_stripos(Request::userAgent(), 'chrome') === false);
        $bJoinItems = ($nType > 0);
        $aFilter[':lang'] = $this->db->langAnd(false, 'C', 'CL');
        $aFilter = $this->prepareFilter($aFilter, 'C');

        $aCategories = $this->db->select('SELECT C.id, CL.title, C.numlevel, ((C.numright-C.numleft)-1) as node
                    ' . ($bJoinItems ? ', COUNT(I.id) as items ' : '') . '
               FROM ' . TABLE_BLOG_CATEGORIES . ' C
                    ' . ($bJoinItems ? ' LEFT JOIN ' . TABLE_BLOG_POSTS . ' I ON C.id = I.cat_id ' : '') . '
                    , ' . TABLE_BLOG_CATEGORIES_LANG . ' CL
               ' . $aFilter['where'] . '
               GROUP BY C.id
               ORDER BY C.numleft', $aFilter['bind']
        );

        $sOptions = '';
        foreach ($aCategories as $v) {
            $nNumlevel = & $v['numlevel'];
            $bDisable = ($nType > 0 && ($nType == 2 ? $v['node'] > 0 : ($nNumlevel > 0 && $v['items'] > 0)));
            $sOptions .= '<option value="' . $v['id'] . '" ' .
                ($bUsePadding && $nNumlevel > 1 ? 'style="padding-left:' . ($nNumlevel * 10) . 'px;" ' : '') .
                ($v['id'] == $nSelectedID ? ' selected' : '') .
                ($bDisable ? ' disabled' : '') .
                '>' . (!$bUsePadding && $nNumlevel > 1 ? str_repeat('  ', $nNumlevel) : '') . $v['title'] . '</option>';
        }

        if ($mEmptyOpt !== false) {
            $nValue = 0;
            if (is_array($mEmptyOpt)) {
                $nValue = key($mEmptyOpt);
                $mEmptyOpt = current($mEmptyOpt);
            }
            $sOptions = '<option value="' . $nValue . '" class="bold">' . $mEmptyOpt . '</option>' . $sOptions;
        }

        return $sOptions;
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_BLOG_POSTS      => array('type' => 'table', 'fields' => $this->langPosts, 'title' => _t('blog', 'Посты')),
            TABLE_BLOG_CATEGORIES => array('type' => 'table', 'fields' => $this->langCategories, 'title' => _t('blog', 'Категории')),
        );
    }
}