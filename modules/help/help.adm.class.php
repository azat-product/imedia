<?php

/**
 * Права доступа группы:
 *  - help: Помощь
 *      - questions: Управление вопросами (список, добавление, редактирование, удаление)
 *      - categories: Управление категориями (список, добавление, редактирование, удаление)
 */
class Help_ extends HelpBase
{
    public function questions()
    {
        if (!$this->haveAccessTo('questions')) {
            return $this->showAccessDenied();
        }

        $oPublicator = $this->initPublicator();

        $bUseBigWysiwyg = config::sysAdmin('help.questions.form.wysiwyg', false, TYPE_BOOL);
        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || $this->isPOST()) {
            $aResponse = array();
            switch ($sAct) {
                case 'add':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $aData = $this->validateQuestionData(0, $bSubmit, $oPublicator);
                    if ($bSubmit) {

                        if ($this->errors->no('help.admin.question.submit',array('id'=>0,'data'=>&$aData,'publicator'=>$oPublicator))) {
                            $nQuestionID = $this->model->questionSave(0, $aData);
                            if ($nQuestionID > 0) {
                                # переносим фотографии в постоянную папку
                                $oPublicator->dataUpdate($aData['content'], $nQuestionID);
                                # link
                                $this->model->questionSave($nQuestionID, array(
                                        'link' => static::url('view', array('id'    => $nQuestionID,
                                                                            'title' => $aData['title'][LNG]
                                                ), true
                                            )
                                    )
                                );
                            }
                        }
                        break;
                    }

                    $aData['id'] = 0;

                    $aData['publicator'] = $oPublicator;
                    $aData['cats'] = $this->model->categoriesOptionsByLevel($this->model->categoryParentsID($aData['cat_id']), array('empty' => _t('','Выбрать')));
                    $aData['big_wy'] = $bUseBigWysiwyg;
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.questions.form');
                }
                break;
                case 'edit':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $nQuestionID = $this->input->postget('id', TYPE_UINT);
                    if (!$nQuestionID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    if ($bSubmit) {

                        $aData = $this->validateQuestionData($nQuestionID, $bSubmit, $oPublicator);
                        if ($this->errors->no('help.admin.question.submit',array('id'=>$nQuestionID,'data'=>&$aData,'publicator'=>$oPublicator))) {
                            $aData['link'] = static::url('view', array('id'    => $nQuestionID,
                                                                       'title' => $aData['title'][LNG]
                                ), true
                            );
                            $this->model->questionSave($nQuestionID, $aData);
                        }
                        $aData['id'] = $nQuestionID;
                        break;
                    } else {
                        $aData = $this->model->questionData($nQuestionID, true);
                        if (empty($aData)) {
                            $this->errors->unknownRecord();
                            break;
                        }
                    }

                    $aData['publicator'] = $oPublicator;
                    $aData['cats'] = $this->model->categoriesOptionsByLevel($this->model->categoryParentsID($aData['cat_id']), array('empty' => _t('','Выбрать')));
                    $aData['big_wy'] = $bUseBigWysiwyg;
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.questions.form');
                }
                break;
                case 'toggle':
                {
                    $nQuestionID = $this->input->postget('id', TYPE_UINT);
                    if (!$nQuestionID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $sToggleType = $this->input->get('type', TYPE_STR);

                    $this->model->questionToggle($nQuestionID, $sToggleType);
                }
                break;
                case 'category-data':
                {
                    $nCategoryID = $aResponse['id'] = $this->input->post('cat_id', TYPE_UINT);
                    if (empty($nCategoryID)) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $bSearch = $this->input->post('search', TYPE_BOOL);

                    $aResponse['subs'] = $this->model->categorySubCount($nCategoryID);
                    if ($aResponse['subs'] > 0) {
                        $aResponse['cats'] = $this->model->categorySubOptions($nCategoryID, array('sel'   => 0,
                                                                                                  'empty' => _t('','Выбрать')
                            )
                        );
                    } else {
                        $aResponse['dp'] = $this->dpForm($nCategoryID, $bSearch);
                    }
                }
                break;
                case 'rotate':
                {

                    $nTab = $this->input->post('tab', TYPE_INT);
                    switch ($nTab) {
                        case 0:
                        {
                            # сортировка в категории
                            $catID = $this->input->post('cat', TYPE_UINT);
                            if ($catID) {
                                $this->model->questionsRotate('num', 'cat_id = ' . $catID);
                            }
                        }
                        break;
                        case 1:
                        {
                            # сортировка "Избранные"
                            $this->model->questionsRotate('fav', 'fav > 0');
                        }
                        break;
                    }
                }
                break;
                case 'delete':
                {

                    $nQuestionID = $this->input->postget('id', TYPE_UINT);
                    if (!$nQuestionID) {
                        $this->errors->impossible();
                        break;
                    }

                    $aData = $this->model->questionData($nQuestionID, true);
                    if (empty($aData)) {
                        $this->errors->impossible();
                        break;
                    }

                    $res = $this->model->questionDelete($nQuestionID);
                    if (!$res) {
                        $this->errors->impossible();
                        break;
                    } else {
                        # удаляем фотографии
                        $oPublicator->dataDelete($aData['content'], $nQuestionID);
                    }
                }
                break;
                default:
                    $aResponse = false;
            }

            if ($aResponse !== false && $this->isAJAX()) {
                $this->ajaxResponseForm($aResponse);
            }
        }

        $f = array();
        $this->input->postgetm(array(
                'page' => TYPE_UINT,
                'cat'  => TYPE_UINT,
                'tab'  => TYPE_INT,
            ), $f
        );

        # формируем фильтр списка вопросов
        $sql = array();
        $sqlOrder = 'created DESC';
        $mPerpage = 15;
        $aData['pgn'] = '';
        $aData['rotate'] = ($f['tab'] == 1 || ($f['cat'] && !$this->model->categorySubCount($f['cat'])));

        switch ($f['tab']) {
            case 0: # Все
            {
                if ($aData['rotate']) {
                    $sqlOrder = 'num';
                }
            }
            break;
            case 1: # Избранные
            {
                $mPerpage = false;
                $sql[':fav'] = 'fav>0';
                $sqlOrder = 'fav';
            }
            break;
        }

        if ($f['cat'] > 0) {
            $sql[':cat_id'] = array('(Q.cat_id2 = :cat OR Q.cat_id1 = :cat)', ':cat' => $f['cat']);
        }

        if ($mPerpage !== false) {
            $nCount = $this->model->questionsListing($sql, true);
            $oPgn = new Pagination($nCount, $mPerpage, '#', 'jHelpQuestionsList.page('.Pagination::PAGE_ID.'); return false;');
            $aData['pgn'] = $oPgn->view(array('arrows'=>false));
            $aData['list'] = $this->model->questionsListing($sql, false, $oPgn->getLimitOffset(), $sqlOrder);
        } else {
            $aData['list'] = $this->model->questionsListing($sql, false, '', $sqlOrder);
        }

        $aData['list'] = $this->viewPHP($aData, 'admin.questions.listing.ajax');

        if ($this->isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['f'] = & $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;

        $aData['cats'] = $this->model->categoriesOptions($f['cat'], array(_t('help','Все категории')));
        tpl::includeJS(array('tablednd', 'wysiwyg'), true);

        return $this->viewPHP($aData, 'admin.questions.listing');
    }

    public function categories()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || $this->isPOST()) {
            $aResponse = array();
            switch ($sAct) {
                case 'add':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $aData = $this->validateCategoryData(0, $bSubmit);
                    if ($bSubmit) {

                        if ($this->errors->no('help.admin.category.submit',array('id'=>0,'data'=>&$aData))) {
                            $nCategoryID = $this->model->categorySave(0, $aData);
                            if ($nCategoryID > 0) {
                            }
                        }
                        break;
                    }

                    $aData['id'] = 0;

                    $aResponse['form'] = $this->viewPHP($aData, 'admin.categories.form');
                }
                break;
                case 'edit':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $nCategoryID = $this->input->postget('id', TYPE_UINT);
                    if (!$nCategoryID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    if ($bSubmit) {

                        $aDataPrev = $this->model->categoryData($nCategoryID);
                        $aData = $this->validateCategoryData($nCategoryID, $bSubmit);
                        if ($this->errors->no('help.admin.category.submit',array('id'=>$nCategoryID,'data'=>&$aData,'before'=>$aDataPrev))) {
                            $res = $this->model->categorySave($nCategoryID, $aData);
                            if (!empty($res) && $aData['keyword_edit'] != $aDataPrev['keyword_edit'] && $aDataPrev['subs']) {
                                # если keyword был изменен и есть вложенные подкатегории:
                                # > перестраиваем полный путь подкатегорий
                                $this->model->categoryRebuildSubsKeyword($nCategoryID, $aDataPrev['keyword_edit']);
                            }
                        }
                        $aData['id'] = $nCategoryID;
                        break;
                    } else {
                        $aData = $this->model->categoryData($nCategoryID, true);
                        if (empty($aData)) {
                            $this->errors->unknownRecord();
                            break;
                        }
                    }

                    $aData['pid_path'] = $this->model->categoryParentsTitle($nCategoryID);
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.categories.form');
                }
                break;
                case 'expand':
                {
                    $nCategoryID = $this->input->postget('id', TYPE_UINT);
                    if (!$nCategoryID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $aData['list'] = $this->model->categoriesListing(array('pid' => $nCategoryID));
                    $aData['skip_norecords'] = false;
                    $aResponse['list'] = $this->viewPHP($aData, 'admin.categories.listing.ajax');
                    $aResponse['cnt'] = sizeof($aData['list']);
                }
                break;
                case 'toggle':
                {

                    $nCategoryID = $this->input->postget('id', TYPE_UINT);
                    if (!$nCategoryID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $sToggleType = $this->input->get('type', TYPE_STR);

                    $this->model->categoryToggle($nCategoryID, $sToggleType);
                }
                break;
                case 'rotate':
                {

                    $this->model->categoriesRotate();
                }
                break;
                case 'dev-delete-all':
                {

                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }
                    $this->model->categoriesDeleteAll();
                    $this->adminRedirect(Errors::SUCCESS, 'categories');
                }
                break;
                case 'dev-treevalidate':
                {

                    # валидация целостности NestedSets категорий
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    return $this->model->treeCategories->validate(true);
                }
                break;
                default:
                    $aResponse = false;
            }

            if ($aResponse !== false && $this->isAJAX()) {
                $this->ajaxResponseForm($aResponse);
            }
        }

        $f = array();

        # формируем фильтр списка категорий
        $sql = array();
        $sqlOrder = 'numleft';
        $aData['pgn'] = '';

        $sExpandState = $this->input->cookie(bff::cookiePrefix() . 'help_categories_expand', TYPE_STR);
        $aExpandID = (!empty($sExpandState) ? explode('.', $sExpandState) : array());
        $aExpandID = array_map('intval', $aExpandID);
        $aExpandID[] = $this->model->categoryRootID();
        $sql[] = 'pid IN (' . join(',', $aExpandID) . ')';

        $aData['list'] = $this->model->categoriesListing($sql, false, '', $sqlOrder);

        $aData['list'] = $this->viewPHP($aData, 'admin.categories.listing.ajax');

        if ($this->isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['f'] = $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;

        return $this->viewPHP($aData, 'admin.categories.listing');
    }

    public function categories_delete()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $nCategoryID = $this->input->getpost('id', TYPE_UINT);
        if (!$nCategoryID) {
            $this->adminRedirect(Errors::IMPOSSIBLE, 'categories');
        }

        $aData = $this->model->categoryData($nCategoryID, true);
        if (!$aData) {
            $this->adminRedirect(Errors::IMPOSSIBLE, 'categories');
        }

        if ($this->isPOST()) {

            $nNextCategoryID = $this->input->post('next', TYPE_UINT);
            if ($nNextCategoryID > 0) {
                # проверяем наличие категории
                $aDataNext = $this->model->categoryData($nNextCategoryID);
                if (empty($aDataNext) || $nNextCategoryID == $nCategoryID || $aDataNext['subs']) {
                    $this->adminRedirect(Errors::IMPOSSIBLE, 'categories');
                }

                # перемещаем вопросы
                $bResult = $this->model->questionsMoveToCategory($nNextCategoryID, $nCategoryID);
                if (!empty($bResult)) {
                    # удаляем категорию
                    $this->model->categoryDelete($nCategoryID);
                }
            } else {
                if (!$aData['questions']) {
                    # удаляем категорию
                    $this->model->categoryDelete($nCategoryID);
                }
            }

            $this->adminRedirect(Errors::SUCCESS, 'categories');
        }

        $aData['categories'] = $this->model->categoriesOptions(0, _t('','Выбрать'), 2);

        return $this->viewPHP($aData, 'admin.categories.delete');
    }

    /**
     * Обрабатываем параметры запроса
     * @param integer $nQuestionID ID вопроса или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @param bff\db\Publicator $oPublicator или FALSE
     * @return array параметры
     */
    protected function validateQuestionData($nQuestionID, $bSubmit, $oPublicator = false)
    {
        $aData = array();
        $this->input->postm_lang($this->model->langQuestions, $aData);
        $this->input->postm(array(
                'cat_id'    => TYPE_UINT, # Категория
                'content'   => TYPE_ARRAY, # Описание
                'content_no'=> TYPE_BOOL, # Без подробного описания
                'enabled'   => TYPE_BOOL, # Включен
                'mtemplate' => TYPE_BOOL, # Использовать общий шаблон SEO
            ), $aData
        );

        if ($bSubmit) {
            # Категория
            $nCategoryID = $aData['cat_id'];
            if (!$nCategoryID) {
                $this->errors->set(_t('','Выберите категорию'));
            } else {
                # проверяем наличие подкатегорий
                $nSubsCnt = $this->model->categorySubCount($nCategoryID);
                if ($nSubsCnt > 0) {
                    $this->errors->set(_t('help','Выбранная категория не должна содержать подкатегории'));
                } else {
                    # сохраняем ID категорий(parent и текущей), для возможности дальнейшего поиска по ним
                    $nParentsID = $this->model->categoryParentsID($nCategoryID, true);
                    foreach ($nParentsID as $lvl => $id) {
                        $aData['cat_id' . $lvl] = $id;
                    }
                }
            }

            # Описание
            if (!empty($oPublicator) && $this->errors->no()) {
                $aDataPublicator = $oPublicator->dataPrepare($aData['content'], $nQuestionID);
                $aData['content'] = $aDataPublicator['content'];
                $aData['content_search'] = $aDataPublicator['content_search'];
            }
        } else {
            if (!$nQuestionID) {
                $aData['mtemplate'] = 1;
            }
        }

        return $aData;
    }

    /**
     * Обрабатываем параметры запроса
     * @param integer $nCategoryID ID категории или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @return array параметры
     */
    protected function validateCategoryData($nCategoryID, $bSubmit)
    {
        $aData = array();
        $this->input->postm_lang($this->model->langCategories, $aData);
        $this->input->postm(array(
                'pid'          => TYPE_UINT, # Основной раздел
                'keyword_edit' => TYPE_NOTAGS, # URL-Keyword
                'enabled'      => TYPE_BOOL, # Включен
                'mtemplate'    => TYPE_BOOL, # Использовать общий шаблон SEO
            ), $aData
        );

        if ($bSubmit) {
            do {
                # URL-Keyword
                $sKeyword = $aData['keyword_edit'];
                if (empty($sKeyword) && !empty($aData['title'][LNG])) {
                    $sKeyword = func::translit($aData['title'][LNG]);
                }
                $sKeyword = preg_replace('/[^\p{L}\w0-9_\-]/iu', '', mb_strtolower($sKeyword));
                $sKeyword = mb_substr(trim($sKeyword, ' -'), 0, 100);
                if (empty($sKeyword)) {
                    $this->errors->set(_t('','Keyword указан некорректно'));
                    break;
                }
                # проверяем уникальность keyword'a в пределах основной категории
                if ($this->model->categoryKeywordExists($sKeyword, $nCategoryID, $aData['pid'])) {
                    $this->errors->set(_t('','Указанный keyword уже используется, укажите другой'));
                    break;
                }
                $aData['keyword_edit'] = $sKeyword;

                # строим полный путь "parent-keyword / ... / keyword"
                $aKeywordsPath = array();
                if ($aData['pid'] > $this->model->categoryRootID()) {
                    $aParentCatData = $this->model->categoryData($aData['pid']);
                    if (empty($aParentCatData)) {
                        $this->errors->set(_t('help','Основная категория указана некорректно'));
                        break;
                    } else {
                        $aKeywordsPath = explode('/', $aParentCatData['keyword']);
                    }
                }
                $aKeywordsPath[] = $sKeyword;
                $aKeywordsPath = join('/', $aKeywordsPath);
                $aData['keyword'] = $aKeywordsPath;

            } while (false);
        } else {
            if (!$nCategoryID) {
                $aData['mtemplate'] = 1;
            }
        }

        return $aData;
    }

    public function ajax()
    {
        $action = $this->input->getpost('act', TYPE_STR);
        switch ($action) {
            case 'empty': {
                //
            } break;
            default: {
                bff::hook('help.admin.ajax.default.action', $action, $this);
                $this->errors->impossible();
            }
        }

        $this->ajaxResponseForm();
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

}