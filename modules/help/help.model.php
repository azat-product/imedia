<?php

define('TABLE_HELP_QUESTIONS',       DB_PREFIX . 'help_questions');
define('TABLE_HELP_QUESTIONS_LANG',  DB_PREFIX . 'help_questions_lang');
define('TABLE_HELP_CATEGORIES',      DB_PREFIX . 'help_categories');
define('TABLE_HELP_CATEGORIES_LANG', DB_PREFIX . 'help_categories_lang');

class HelpModel_ extends Model
{
    /** @var HelpBase */
    var $controller;

    public $langQuestions = array(
        'title'          => TYPE_STR, # Заголовок
        'textshort'      => TYPE_STR, # Краткое описание
        'mtitle'         => TYPE_NOTAGS, # Meta Title
        'mkeywords'      => TYPE_NOTAGS, # Meta Keywords
        'mdescription'   => TYPE_NOTAGS, # Meta Description
        'content_search' => TYPE_STR, # Publicator-поиск
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
        $this->treeCategories = new bff\db\NestedSetsTree(TABLE_HELP_CATEGORIES);
        $this->treeCategories->init();
    }

    public function categoryRootID()
    {
        return 1; # ID "Корневой категории" (изменять не рекомендуется)
    }

    # --------------------------------------------------------------------
    # Вопросы

    /**
     * Список вопросов (admin)
     * @param array $aFilter фильтр списка вопросов
     * @param bool $bCount только подсчет кол-ва вопросов
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function questionsListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        if ($bCount) {
            $aFilter = $this->prepareFilter($aFilter, 'Q');
            return $this->db->tag('help-questions-listing-count', array('filter'=>&$aFilter, 'join'=>&$sqlJoin))->one_data('SELECT COUNT(Q.id)
                FROM ' . TABLE_HELP_QUESTIONS . ' Q
                     ' . $aFilter['where'], $aFilter['bind']
            );
        }

        $sqlJoin = ' LEFT JOIN ' . TABLE_HELP_CATEGORIES_LANG . ' C ON C.id = Q.cat_id1 AND C.lang = :lang';

        $aFilter[':lang'] = $this->db->langAnd(false, 'Q', 'QL');
        $aFilter = $this->prepareFilter($aFilter, 'Q', array(':lang' => $this->locale->getCurrentLanguage()));

        return $this->db->tag('help-questions-listing-data', array('filter'=>&$aFilter, 'join'=>&$sqlJoin))->select('SELECT Q.id, Q.link, Q.created, QL.title, Q.enabled, Q.content_no, Q.fav, Q.cat_id, C.title as cat_title
               FROM ' . TABLE_HELP_QUESTIONS . ' Q ' . $sqlJoin . ', ' . TABLE_HELP_QUESTIONS_LANG . ' QL
               ' . $aFilter['where'] .
            ' GROUP BY Q.id '
            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Поиск вопросов (frontend)
     * @param string $sQuery фильтр списка вопросов
     * @param bool $bCount только подсчет кол-ва вопросов
     * @param string $sqlLimit
     * @return mixed
     */
    public function questionsSearch($sQuery = array(), $bCount = false, $sqlLimit = '')
    {
        $aFilter = array('enabled' => 1);
        $sQueryFT = $this->db->prepareFulltextQuery($sQuery, 'QL.title, QL.textshort, QL.content_search');
        $aFilter[':query'] = $sQueryFT;
        $aFilter[':lang'] = $this->db->langAnd(false, 'Q', 'QL');

        $aFilter = $this->prepareFilter($aFilter, 'Q');

        if ($bCount) {
            return $this->db->tag('help-questions-search-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(Q.id)
                FROM ' . TABLE_HELP_QUESTIONS . ' Q, ' . TABLE_HELP_QUESTIONS_LANG . ' QL
                ' . $aFilter['where'], $aFilter['bind']
            );
        }

        $aData = $this->db->tag('help-questions-search-data', array('filter'=>&$aFilter))->select('SELECT QL.title, Q.link, QL.textshort, Q.content_no
                                  FROM ' . TABLE_HELP_QUESTIONS . ' Q, ' . TABLE_HELP_QUESTIONS_LANG . ' QL
                                  ' . $aFilter['where'] . '
                                  ORDER BY QL.title ' . $sqlLimit, $aFilter['bind']
        );

        return $aData;
    }

    /**
     * Список частых вопросов (frontend)
     * @return mixed
     */
    public function questionsFav()
    {
        $aFilter = array('enabled' => 1, 'fav>0');
        $aFilter[':lang'] = $this->db->langAnd(false, 'Q', 'QL');
        $aFilter = $this->prepareFilter($aFilter, 'Q');

        return $this->db->tag('help-questions-fav-data', array('filter'=>&$aFilter))->select('SELECT Q.link, QL.title, Q.content_no
               FROM ' . TABLE_HELP_QUESTIONS . ' Q, ' . TABLE_HELP_QUESTIONS_LANG . ' QL
               ' . $aFilter['where']
            . ' ORDER BY fav', $aFilter['bind']
        );
    }

    /**
     * Получение данных вопроса
     * @param integer $nQuestionID ID вопроса
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function questionData($nQuestionID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT Q.*
                    FROM ' . TABLE_HELP_QUESTIONS . ' Q
                    WHERE Q.id = :id',
                array(':id' => $nQuestionID)
            );
            if (!empty($aData)) {
                $this->db->langSelect($nQuestionID, $aData, $this->langQuestions, TABLE_HELP_QUESTIONS_LANG);
            }
        } else {
            //
        }

        return $aData;
    }

    /**
     * Просмотр вопроса (frontend)
     * @param integer $nQuestionID ID вопроса
     * @return array
     */
    public function questionView($nQuestionID)
    {
        $aData = $this->db->tag('help-question-view-data')->one_array('SELECT Q.id, Q.cat_id, Q.link, QL.title, Q.content, QL.textshort,
                        QL.mtitle, QL.mkeywords, QL.mdescription, Q.mtemplate, Q.content_no
                    FROM ' . TABLE_HELP_QUESTIONS . ' Q,
                         ' . TABLE_HELP_QUESTIONS_LANG . ' QL
                    WHERE Q.id = :id AND Q.enabled = 1
                        ' . $this->db->langAnd(true, 'Q', 'QL') . '
                    ', array(':id' => $nQuestionID)
        );
        if (empty($aData)) {
            return false;
        }

        # другие вопросы
        $aData['questions_other'] = $this->db->tag('help-question-view-other')->select('SELECT Q.link, QL.title, Q.content_no
                    FROM ' . TABLE_HELP_QUESTIONS . ' Q,
                         ' . TABLE_HELP_QUESTIONS_LANG . ' QL
                    WHERE Q.cat_id = :cat AND Q.id != :id AND Q.enabled = 1
                        ' . $this->db->langAnd(true, 'Q', 'QL') . '
                    ', array(':cat' => $aData['cat_id'], ':id' => $nQuestionID)
        );

        # хлебные крошки
        $aData['crumbs'] = $this->db->tag('help-question-view-crumbs')->select('SELECT C.keyword, CL.title
                        FROM ' . TABLE_HELP_CATEGORIES . ' C,
                             ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                        WHERE ' . $this->db->prepareIN('C.id', $this->categoryParentsID($aData['cat_id'])) .
            $this->db->langAnd(true, 'C', 'CL')
        );

        return $aData;
    }

    /**
     * Сохранение вопроса
     * @param integer $nQuestionID ID вопроса
     * @param array $aData данные вопроса
     * @return boolean|integer
     */
    public function questionSave($nQuestionID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nQuestionID > 0) {
            $aData['modified'] = $this->db->now();

            $res = $this->db->update(TABLE_HELP_QUESTIONS, array_diff_key($aData, $this->langQuestions), array('id' => $nQuestionID));
            $this->db->langUpdate($nQuestionID, $aData, $this->langQuestions, TABLE_HELP_QUESTIONS_LANG);

            return !empty($res);
        } else {
            $aData['created'] = $this->db->now();
            $aData['modified'] = $this->db->now();
            $aData['user_id'] = $this->security->getUserID();
            $aData['num'] = intval($this->db->one_data('SELECT MAX(num)
                                FROM ' . TABLE_HELP_QUESTIONS . ' WHERE cat_id = :cat', array(':cat' => $aData['cat_id'])
                    )
                ) + 1;

            $nQuestionID = $this->db->insert(TABLE_HELP_QUESTIONS, array_diff_key($aData, $this->langQuestions));
            if ($nQuestionID > 0) {
                $this->db->langInsert($nQuestionID, $aData, $this->langQuestions, TABLE_HELP_QUESTIONS_LANG);
                //
            }

            return $nQuestionID;
        }
    }

    /**
     * Переключатели вопроса
     * @param integer $nQuestionID ID вопроса
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function questionToggle($nQuestionID, $sField)
    {
        switch ($sField) {
            case 'enabled':
                return $this->toggleInt(TABLE_HELP_QUESTIONS, $nQuestionID, $sField, 'id');
                break;
            case 'fav':
                return $this->toggleInt(TABLE_HELP_QUESTIONS, $nQuestionID, $sField, 'id', true);
                break;
        }
    }

    /**
     * Перемещение вопроса
     * @param string $sOrderField поле, по которому производится сортировка
     * @param string $aCond дополнительные условия
     * @return mixed @see rotateTablednd
     */
    public function questionsRotate($sOrderField, $aCond = '')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . (is_array($aCond) ? join(' AND ', $aCond) : $aCond);
        }

        return $this->db->rotateTablednd(TABLE_HELP_QUESTIONS, $aCond, 'id', $sOrderField);
    }

    /**
     * Перемещение вопросов из одной категории ($nCatOldID) в другую ($nCatNewID)
     * @param int $nCatNewID ID категории, в которую перемещаем вопросы
     * @param int $nCatOldID ID категории, из которой перемещаем вопросы
     * @return mixed
     */
    public function questionsMoveToCategory($nCatNewID, $nCatOldID)
    {
        if (empty($nCatNewID) || empty($nCatOldID)) {
            return false;
        }
        # перемещаем
        $aNewData = $this->categoryData($nCatNewID);
        if (empty($aNewData)) {
            return false;
        }
        $aUpdate = array('cat_id' => $nCatNewID);
        $aParentsID = $this->categoryParentsID($nCatNewID, true, true);
        if (!empty($aParentsID)) {
            foreach ($aParentsID as $lvl => $id) {
                $aUpdate['cat_id' . $lvl] = $id;
            }
        }

        return $this->db->update(TABLE_HELP_QUESTIONS, $aUpdate, array('cat_id' => $nCatOldID));
    }

    /**
     * Удаление вопроса
     * @param integer $nQuestionID ID вопроса
     * @return boolean
     */
    public function questionDelete($nQuestionID)
    {
        if (empty($nQuestionID)) {
            return false;
        }
        $res = $this->db->delete(TABLE_HELP_QUESTIONS, array('id' => $nQuestionID));
        if (!empty($res)) {
            $this->db->delete(TABLE_HELP_QUESTIONS_LANG, array('id' => $nQuestionID));
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
    public function categoriesListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        $aFilter[':lang'] = $this->db->langAnd(false, 'C', 'CL');
        $aFilter[] = 'pid != 0';
        $aFilter = $this->prepareFilter($aFilter, 'C');

        if ($bCount) {
            return $this->db->tag('help-categories-listing-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(C.id) FROM ' . TABLE_HELP_CATEGORIES . ' C, ' . TABLE_HELP_CATEGORIES_LANG . ' CL ' . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->tag('help-categories-listing-data', array('filter'=>&$aFilter))->select('SELECT C.id, C.created, CL.title, C.enabled, C.pid, C.numlevel, ((C.numright-C.numleft)-1) as subs
               FROM ' . TABLE_HELP_CATEGORIES . ' C, ' . TABLE_HELP_CATEGORIES_LANG . ' CL
               ' . $aFilter['where']
            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Список категорий (frontend)
     * @return mixed
     */
    public function categoriesListIndex()
    {
        $aFilter = array(
            'pid != 0',
            'enabled' => 1,
            ':lang'   => $this->db->langAnd(false, 'C', 'CL'),
        );
        $aFilter = $this->prepareFilter($aFilter, 'C');

        $aData = $this->db->tag('help-categories-list-index-data', array('filter'=>&$aFilter))->select_key('SELECT C.id, CL.title, C.keyword, C.pid, COUNT(Q.id) as questions
                                  FROM ' . TABLE_HELP_CATEGORIES . ' C
                                        LEFT JOIN ' . TABLE_HELP_QUESTIONS . ' Q ON Q.cat_id = C.id AND Q.enabled = 1
                                     , ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                                  ' . $aFilter['where'] . '
                                  GROUP BY C.id
                                  ORDER BY C.numleft', 'id',
            $aFilter['bind']
        );

        if (!empty($aData)) {
            $aCategoryID = array();
            foreach ($aData as $k => &$v) {
                if ($v['questions'] > 0) {
                    $v['questions_list'] = array();
                    $aCategoryID[] = $k;
                }
            }
            unset($v);

            $aQuestions = $this->db->tag('help-categories-list-index-questions')->select('SELECT Q.cat_id, Q.link, QL.title, Q.content_no
                            FROM ' . TABLE_HELP_QUESTIONS . ' Q,
                                 ' . TABLE_HELP_QUESTIONS_LANG . ' QL
                            WHERE Q.enabled = 1 AND ' . $this->db->prepareIN('Q.cat_id', $aCategoryID) . '
                                AND ' . $this->db->langAnd(false, 'Q', 'QL') . '
                            ORDER BY Q.num'
            );
            if (!empty($aQuestions)) {
                foreach ($aQuestions as $v) {
                    $aData[$v['cat_id']]['questions_list'][] = $v;
                }
            }
            unset($aQuestions);

            $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'subcats');
        }

        return $aData;
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
            $aData = $this->db->one_array('SELECT C.*, ((C.numright-C.numleft)-1) as subs, COUNT(Q.id) as questions
                    FROM ' . TABLE_HELP_CATEGORIES . ' C
                        LEFT JOIN ' . TABLE_HELP_QUESTIONS . ' Q ON Q.cat_id = C.id
                    WHERE C.id = :id
                    GROUP BY C.id',
                array(':id' => $nCategoryID)
            );
            if (!empty($aData)) {
                $this->db->langSelect($nCategoryID, $aData, $this->langCategories, TABLE_HELP_CATEGORIES_LANG);
            }
        } else {
            $aData = $this->db->one_array('SELECT C.*, ((C.numright-C.numleft)-1) as subs
                FROM ' . TABLE_HELP_CATEGORIES . ' C
                WHERE C.id = :id', array(':id' => $nCategoryID)
            );
        }

        return $aData;
    }

    /**
     * Просмотр категории + вопросов (или подкатегорий)
     * @param string $sKeyword keyword категории
     * @return array|boolean
     */
    public function categoryView($sKeyword)
    {
        $aData = $this->db->tag('help-category-view-data')->one_array('SELECT C.id, C.pid, C.keyword, CL.title,
                            CL.mtitle, CL.mkeywords, CL.mdescription, C.mtemplate,
                            ((C.numright-C.numleft)-1) as subcats
                        FROM ' . TABLE_HELP_CATEGORIES . ' C,
                             ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                        WHERE C.keyword = :key AND C.enabled = 1
                            AND ' . $this->db->langAnd(false, 'C', 'CL') . '
                    ', array(':key' => $sKeyword)
        );
        if (!empty($aData)) {
            $nCategoryID = $aData['id'];

            # хлебные крошки
            $aData['crumbs'] = $this->db->tag('help-category-view-crumbs')->select('SELECT C.keyword, CL.title
                            FROM ' . TABLE_HELP_CATEGORIES . ' C,
                                 ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                            WHERE ' . $this->db->prepareIN('C.id', $this->categoryParentsID($nCategoryID)) .
                $this->db->langAnd(true, 'C', 'CL')
            );

            if (!$aData['subcats']) {
                # вопросы
                $aData['questions_list'] = $this->db->tag('help-category-view-questions')->select('SELECT Q.link, QL.title, QL.textshort, Q.content_no
                        FROM ' . TABLE_HELP_QUESTIONS . ' Q,
                             ' . TABLE_HELP_QUESTIONS_LANG . ' QL
                        WHERE Q.enabled = 1 AND Q.cat_id = :cat
                            AND ' . $this->db->langAnd(false, 'Q', 'QL') . '
                        ORDER BY Q.num', array(':cat' => $nCategoryID)
                );
            } else {
                # подкатегории
                $aData['subcats_list'] = $this->db->tag('help-category-view-subcats')->select('SELECT C.keyword, CL.title
                        FROM ' . TABLE_HELP_CATEGORIES . ' C,
                             ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                        WHERE C.enabled = 1 AND C.pid = :cat
                            AND ' . $this->db->langAnd(false, 'C', 'CL') . '
                        ORDER BY C.numleft', array(':cat' => $nCategoryID)
                );
            }
        }

        return $aData;
    }

    /**
     * Получение данных категории
     * @param string $sKeyword keyword
     * @param integer $nCategoryID ID категории
     * @param integer $nCaregoryParentID ID parent-категории
     * @return boolean
     */
    public function categoryKeywordExists($sKeyword, $nCategoryID, $nCaregoryParentID)
    {
        $aFilter = $this->prepareFilter(array(
                'pid'          => $nCaregoryParentID,
                'keyword_edit' => $sKeyword,
                array('id != :id', ':id' => $nCategoryID)
            )
        );
        $aData = $this->db->one_array('SELECT id
                       FROM ' . TABLE_HELP_CATEGORIES . '
                       ' . $aFilter['where'] . '
                       LIMIT 1', $aFilter['bind']
        );

        return !empty($aData);
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
                unset($aData['pid']);
            } # запрет изменения pid
            $res = $this->db->update(TABLE_HELP_CATEGORIES, array_diff_key($aData, $this->langCategories), array('id' => $nCategoryID));

            $this->db->langUpdate($nCategoryID, $aData, $this->langCategories, TABLE_HELP_CATEGORIES_LANG);

            return !empty($res);
        } else {

            $aData['created'] = $this->db->now();
            $aData['modified'] = $this->db->now();

            $nCategoryID = $this->treeCategories->insertNode($aData['pid']);
            if ($nCategoryID > 0) {
                unset($aData['pid']);
                $this->db->update(TABLE_HELP_CATEGORIES, array_diff_key($aData, $this->langCategories), 'id = :id', array(':id' => $nCategoryID));
                $this->db->langInsert($nCategoryID, $aData, $this->langCategories, TABLE_HELP_CATEGORIES_LANG);
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
                return $this->toggleInt(TABLE_HELP_CATEGORIES, $nCategoryID, $sField, 'id');
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
        $nSubCnt = $this->categorySubCount($nCategoryID);
        if (!empty($nSubCnt)) {
            $this->errors->set(_t('help','Невозможно выполнить удаление категории при наличии подкатегорий'));

            return false;
        }

        $nItems = $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_HELP_QUESTIONS . ' I WHERE I.cat_id = :id', array(':id' => $nCategoryID));
        if (!empty($nItems)) {
            $this->errors->set(_t('help','Невозможно выполнить удаление категории при наличии вложенных элементов'));

            return false;
        }

        $aDeletedID = $this->treeCategories->deleteNode($nCategoryID);
        $res = !empty($aDeletedID);
        if (!empty($res)) {
            $this->db->delete(TABLE_HELP_CATEGORIES_LANG, array('id' => $nCategoryID));

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
        $this->db->exec('DELETE FROM ' . TABLE_HELP_CATEGORIES . ' WHERE id > 0');
        $this->db->exec('TRUNCATE TABLE ' . TABLE_HELP_CATEGORIES_LANG);
        $this->db->exec('ALTER TABLE ' . TABLE_HELP_CATEGORIES . ' AUTO_INCREMENT = 2');
        $this->db->update(TABLE_HELP_QUESTIONS, array('cat_id' => 0, 'cat_id1' => 0, 'cat_id2' => 0));

        # создаем корневую директорию
        $nRootID = $this->categoryRootID();
        $sRootTitle = _t('help','Корневой раздел');
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
        $res = $this->db->insert(TABLE_HELP_CATEGORIES, $aData);
        if (!empty($res)) {
            $aDataLang = array('title' => array());
            foreach ($this->locale->getLanguages() as $lng) {
                $aDataLang['title'][$lng] = $sRootTitle;
            }
            $this->db->langInsert($nRootID, $aDataLang, $this->langCategories, TABLE_HELP_CATEGORIES_LANG);
        }

        return !empty($res);
    }

    /**
     * Получаем кол-во вложенных категорий
     */
    public function categorySubCount($nCategoryID)
    {
        return $this->treeCategories->getChildrenCount($nCategoryID);
    }

    /**
     * Формирование списка подкатегорий
     * @param integer $nCategoryID ID категории
     * @param mixed $mOptions формировать select-options или FALSE
     * @return array|string
     */
    public function categorySubOptions($nCategoryID, $mOptions = false)
    {
        $aData = $this->db->select('SELECT C.id, CL.title
                    FROM ' . TABLE_HELP_CATEGORIES . ' C, ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                    WHERE C.pid = :pid
                      ' . $this->db->langAnd(true, 'C', 'CL') . '
                    ORDER BY C.numleft', array(':pid' => $nCategoryID)
        );

        if (empty($mOptions)) {
            return $aData;
        }

        return HTML::selectOptions($aData, $mOptions['sel'], $mOptions['empty'], 'id', 'title');
    }

    /**
     * Обработка редактирования keyword'a в категории с подменой его в путях подкатегорий
     * @param integer $nCategoryID ID категории
     * @param string $sKeywordPrev предыдущий keyword
     * @return boolean
     */
    public function categoryRebuildSubsKeyword($nCategoryID, $sKeywordPrev)
    {
        $aCatData = $this->categoryData($nCategoryID);
        if (empty($aCatData)) {
            return false;
        }
        if ($aCatData['pid'] == $this->categoryRootID()) {
            $sFrom = $sKeywordPrev . '/';
        } else {
            $aParentCatData = $this->categoryData($aCatData['pid']);
            if (empty($aParentCatData)) {
                return false;
            }
            $sFrom = $aParentCatData['keyword'] . '/' . $sKeywordPrev . '/';
        }

        # перестраиваем полный путь подкатегорий
        $res = $this->db->update(TABLE_HELP_CATEGORIES,
            array('keyword = REPLACE(keyword, :from, :to)'),
            'numleft > :left AND numright < :right',
            array(
                ':from'  => $sFrom,
                ':to'    => $aCatData['keyword'] . '/',
                ':left'  => $aCatData['numleft'],
                ':right' => $aCatData['numright']
            )
        );

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
            $aFilter[] = 'numlevel < 2';
        } else {
            $aFilter[] = 'numlevel > 0';
        }
        if (!empty($aOnlyID)) {
            $aFilter[':only'] = '(C.id IN (' . join(',', $aOnlyID) . ') OR C.pid IN(' . join(',', $aOnlyID) . '))';
        }

        # Chrome не понимает style="padding" в option
        $bUsePadding = (mb_stripos(Request::userAgent(), 'chrome') === false);
        $bJoinItems = ($nType > 0);
        $aFilter[':lang'] = $this->db->langAnd(false, 'C', 'CL');
        $aFilter = $this->prepareFilter($aFilter, 'C');

        $aCategories = $this->db->select('SELECT C.id, CL.title, C.numlevel, ((C.numright-C.numleft)-1) as subs
                    ' . ($bJoinItems ? ', COUNT(I.id) as items ' : '') . '
               FROM ' . TABLE_HELP_CATEGORIES . ' C
                    ' . ($bJoinItems ? ' LEFT JOIN ' . TABLE_HELP_QUESTIONS . ' I ON C.id = I.cat_id ' : '') . '
                    , ' . TABLE_HELP_CATEGORIES_LANG . ' CL
               ' . $aFilter['where'] . '
               GROUP BY C.id
               ORDER BY C.numleft', $aFilter['bind']
        );

        $sOptions = '';
        foreach ($aCategories as &$v) {
            $nNumlevel = & $v['numlevel'];
            $bDisable = ($nType > 0 && ($nType == 2 ? $v['subs'] > 0 : ($nNumlevel > 0 && $v['items'] > 0)));
            $sOptions .= '<option value="' . $v['id'] . '" ' .
                ($bUsePadding && $nNumlevel > 1 ? 'style="padding-left:' . ($nNumlevel * 10) . 'px;" ' : '') .
                ($v['id'] == $nSelectedID ? ' selected' : '') .
                ($bDisable ? ' disabled' : '') .
                '>' . (!$bUsePadding && $nNumlevel > 1 ? str_repeat('  ', $nNumlevel) : '') . $v['title'] . '</option>';
        }
        unset($v);

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

    /**
     * Формирование списков категорий (при добавлении/редактировании записи)
     * @param array $aCategoriesID ID категорий [lvl=>selectedID, ...]
     * @param mixed $mOptions формировать select-options или нет (false)
     * @return array [lvl=>[a=>selectedID, categories=>список категорий(массив или options)],...]
     */
    public function categoriesOptionsByLevel($aCategoriesID, $mOptions = false)
    {
        if (empty($aCategoriesID)) {
            return array();
        }

        # формируем список требуемых уровней категорий
        $aLevels = array();
        $bFill = true;
        $parentID = $this->categoryRootID();
        foreach ($aCategoriesID as $lvl => $nCategoryID) {
            if ($nCategoryID || $bFill) {
                $aLevels[$lvl] = $parentID;
                if (!$nCategoryID) {
                    break;
                }
                $parentID = $nCategoryID;
            } else {
                break;
            }
        }

        if (empty($aLevels)) {
            return array();
        }

        $aData = $this->db->select('SELECT C.id, CL.title, C.numlevel as lvl
                    FROM ' . TABLE_HELP_CATEGORIES . ' C, ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                    WHERE C.numlevel IN (' . join(',', array_keys($aLevels)) . ')
                      AND C.pid IN(' . join(',', $aLevels) . ')
                      ' . $this->db->langAnd(true, 'C', 'CL') . '
                    ORDER BY C.numleft'
        );
        if (empty($aData)) {
            return array();
        }

        $aLevels = array();
        foreach ($aData as $v) {
            $aLevels[$v['lvl']][$v['id']] = $v;
        }
        unset($aData);

        foreach ($aCategoriesID as $lvl => $nSelectedID) {
            if (isset($aLevels[$lvl])) {
                $aCategoriesID[$lvl] = array(
                    'a'          => $nSelectedID,
                    'categories' => (!empty($mOptions) ?
                            HTML::selectOptions($aLevels[$lvl], $nSelectedID, $mOptions['empty'], 'id', 'title') :
                            $aLevels[$lvl]),
                );
            } else {
                $aCategoriesID[$lvl] = array(
                    'a'          => $nSelectedID,
                    'categories' => false,
                );
            }
        }

        return $aCategoriesID;
    }

    /**
     * Получаем данные parent-категорий
     * @param integer $nCategoryID ID категории
     * @param bool $bIncludingSelf включать текущую в итоговых список
     * @param bool $bExludeRoot исключить корневой раздел
     * @return array array(lvl=>id, ...)
     */
    public function categoryParentsID($nCategoryID, $bIncludingSelf = true, $bExludeRoot = true)
    {
        if (empty($nCategoryID)) {
            return array(1 => 0);
        }
        $aData = $this->treeCategories->getNodeParentsID($nCategoryID, ($bExludeRoot ? ' AND numlevel > 0' : ''), $bIncludingSelf, array(
                'id',
                'numlevel'
            )
        );
        $aParentsID = array();
        if (!empty($aData)) {
            foreach ($aData as $v) {
                $aParentsID[$v['numlevel']] = $v['id'];
            }
        }

        return $aParentsID;
    }

    /**
     * Получаем названия parent-категорий
     * @param integer $nCategoryID ID категории
     * @param boolean $bIncludingSelf включать текущую в итоговых список
     * @param boolean $bExludeRoot исключить корневой раздел
     * @param mixed $mSeparator объединить в одну строку или FALSE
     * @return array array(lvl=>id, ...)
     */
    public function categoryParentsTitle($nCategoryID, $bIncludingSelf = false, $bExludeRoot = false, $mSeparator = true)
    {
        $aParentsID = $this->treeCategories->getNodeParentsID($nCategoryID, ($bExludeRoot ? ' AND numlevel > 0' : ''), $bIncludingSelf);
        if (empty($aParentsID)) {
            return ($mSeparator !== false ? '' : array());
        }

        $aData = $this->db->select_one_column('SELECT CL.title
                   FROM ' . TABLE_HELP_CATEGORIES . ' C, ' . TABLE_HELP_CATEGORIES_LANG . ' CL
                   WHERE ' . $this->db->prepareIN('C.id', $aParentsID) . '' . $this->db->langAnd(true, 'C', 'CL') . '
                   ORDER BY C.numleft'
        );

        $aData = (!empty($aData) ? $aData : array());

        if ($mSeparator !== false) {
            return join('  ' . ($mSeparator === true ? '>' : $mSeparator) . '  ', $aData);
        } else {
            return $aData;
        }
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_HELP_QUESTIONS  => array('type' => 'table', 'fields' => $this->langQuestions, 'title' => _t('help', 'Вопросы')),
            TABLE_HELP_CATEGORIES => array('type' => 'table', 'fields' => $this->langCategories, 'title' => _t('help', 'Категории')),
        );
    }

}