<?php

/**
 * Права доступа группы:
 *  - blog: Блог
 *      - posts: Управление постами (список, добавление, редактирование, удаление)
 *      - tags: Управление тегами (список, добавление, редактирование, удаление)
 *      - categories: Управление категориями (список, добавление, редактирование, удаление)
 *      - settings: Дополнительные настройки
 */
class Blog_ extends BlogBase
{
    public function posts()
    {
        if (!$this->haveAccessTo('posts')) {
            return $this->showAccessDenied();
        }

        $oPublicator = $this->initPublicator();

        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || Request::isPOST()) {
            $aResponse = array();
            switch ($sAct) {
                case 'add':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $aData = $this->validatePostData(0, $bSubmit, $oPublicator);
                    if ($bSubmit) {

                        if ($this->errors->no('blog.admin.post.submit',array('id'=>0,'data'=>&$aData,'publicator'=>$oPublicator))) {
                            $nPostID = $this->model->postSave(0, $aData);
                            if ($nPostID > 0) {
                                # теги
                                if (static::tagsEnabled()) {
                                    $this->postTags()->tagsSave($nPostID);
                                }
                                # переносим фотографии в постоянную папку
                                $oPublicator->dataUpdate($aData['content'], $nPostID);
                                # link
                                $this->model->postSave($nPostID, array(
                                        'link' => static::url('view', array('id'    => $nPostID,
                                                                            'title' => $aData['title'][LNG]
                                                ), true
                                            )
                                    )
                                );
                                # загружаем превью
                                $mPreview = $this->postPreview($nPostID)->onSubmit(true, 'preview', 'preview_del');
                                if ($mPreview !== false) {
                                    $this->model->postSave($nPostID, array('preview' => $mPreview));
                                }
                            }
                        }
                        $this->iframeResponseForm($aResponse);
                        break;
                    }

                    $aData['id'] = 0;

                    $aData['publicator'] = $oPublicator;
                    $aData['cats'] = $this->model->categoriesOptions($aData['cat_id'], array('empty' => _t('','Выбрать')));
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.posts.form');
                }
                break;
                case 'edit':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $nPostID = $this->input->postget('id', TYPE_UINT);
                    if (!$nPostID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    if ($bSubmit) {

                        $aData = $this->validatePostData($nPostID, $bSubmit, $oPublicator);
                        if ($this->errors->no('blog.admin.post.submit',array('id'=>$nPostID,'data'=>&$aData,'publicator'=>$oPublicator))) {
                            $aData['link'] = static::url('view', array('id'    => $nPostID,
                                                                       'title' => $aData['title'][LNG]
                                ), true
                            );

                            # обновляем превью (если необходимо)
                            $mPreview = $this->postPreview($nPostID)->onSubmit(false, 'preview', 'preview_del');
                            if ($mPreview !== false) {
                                $aData['preview'] = $mPreview;
                                $aResponse['reload'] = 1;
                            }

                            $this->model->postSave($nPostID, $aData);
                            # теги
                            if (static::tagsEnabled()) {
                                $this->postTags()->tagsSave($nPostID);
                            }
                        }
                        $aData['id'] = $nPostID;
                        $this->iframeResponseForm($aResponse);
                        break;
                    } else {
                        $aData = $this->model->postData($nPostID, true);
                        if (empty($aData)) {
                            $this->errors->unknownRecord();
                            break;
                        }
                    }

                    $aData['publicator'] = $oPublicator;
                    $aData['cats'] = $this->model->categoriesOptions($aData['cat_id'],
                        (!$aData['cat_id'] ? array('empty' => _t('','Выбрать')) : false)
                    );
                    $aData['preview_list'] = BlogPostPreview::url($nPostID, $aData['preview'], BlogPostPreview::szList);
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.posts.form');
                }
                break;
                case 'toggle':
                {

                    $nPostID = $this->input->postget('id', TYPE_UINT);
                    if (!$nPostID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $sToggleType = $this->input->get('type', TYPE_STR);

                    $this->model->postToggle($nPostID, $sToggleType);
                }
                break;
                case 'rotate':
                {

                    $nTab = $this->input->post('tab', TYPE_INT);
                    switch ($nTab) {
                        case 1:
                        {
                            # сортировка "Избранные"
                            $this->model->postsRotate('fav', 'fav > 0');
                        }
                        break;
                    }
                }
                break;
                case 'delete':
                {

                    $nPostID = $this->input->postget('id', TYPE_UINT);
                    if (!$nPostID) {
                        $this->errors->impossible();
                        break;
                    }

                    $aData = $this->model->postData($nPostID, true);
                    if (empty($aData)) {
                        $this->errors->impossible();
                        break;
                    }

                    $res = $this->model->postDelete($nPostID);
                    if (!$res) {
                        $this->errors->impossible();
                        break;
                    } else {
                        # теги
                        $this->postTags()->onItemDelete($nPostID);
                        # удаляем фотографии
                        $oPublicator->dataDelete($aData['content'], $nPostID);
                    }
                }
                break;
                case 'tags-suggest': # autocomplete.fb
                {
                    $sQuery = $this->input->postget('tag', TYPE_STR);
                    $this->postTags()->tagsAutocomplete($sQuery);
                }
                break;
                case 'tags-autocomplete': # autocomplete
                {
                    $sQuery = $this->input->post('q', TYPE_STR);
                    $sQuery = $this->input->cleanSearchString($sQuery, 100);
                    $this->postTags()->tagsAutocomplete($sQuery);
                }
                break;
                default:
                    $aResponse = false;
            }

            if ($aResponse !== false && Request::isAJAX()) {
                $this->ajaxResponseForm($aResponse);
            }
        }

        $f = array();
        $this->input->postgetm(array(
                'page' => TYPE_UINT,
                'cat'  => TYPE_UINT,
                'tab'  => TYPE_UINT,
                'tag'  => TYPE_UINT,
            ), $f
        );

        # формируем фильтр списка постов
        $sql = array();
        $sqlOrder = '';
        $mPerpage = 15;
        $aData['pgn'] = '';

        switch ($f['tab']) {
            case 0:
            {
                $sqlOrder = 'created DESC';
            }
                break; # Все
            case 1:
            {
                $mPerpage = false;
                $sql[':fav'] = 'fav>0';
                $sqlOrder = 'fav';
            }
                break; # Избранные
        }

        if ($f['cat'] > 0) {
            $sql[':cat_id'] = array('(P.cat_id = :cat)', ':cat' => $f['cat']);
        }

        if ($f['tag'] > 0) {
            $aData['tag'] = $this->postTags()->tagData($f['tag']);
            if (empty($aData['tag'])) {
                $f['tag'] = 0;
            }
        }

        if ($mPerpage !== false) {
            $nCount = $this->model->postsListing($sql, $f['tag'], true);
            $oPgn = new Pagination($nCount, $mPerpage, '#', 'jBlogPostsList.page('.Pagination::PAGE_ID.'); return false;');
            $aData['pgn'] = $oPgn->view(array('arrows'=>false));
            $aData['list'] = $this->model->postsListing($sql, $f['tag'], false, $oPgn->getLimitOffset(), $sqlOrder);
        } else {
            $aData['list'] = $this->model->postsListing($sql, $f['tag'], false, '', $sqlOrder);
        }

        $aData['list'] = $this->viewPHP($aData, 'admin.posts.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['f'] = $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;

        $aData['cats'] = $this->model->categoriesOptions($f['cat'], array(_t('blog','Все категории')));

        return $this->viewPHP($aData, 'admin.posts.listing');
    }

    public function tags()
    {
        if (!$this->haveAccessTo('tags')) {
            return $this->showAccessDenied();
        }

        return $this->postTags()->manage();
    }

    public function categories()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || Request::isPOST()) {
            $aResponse = array();
            switch ($sAct) {
                case 'add':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $aData = $this->validateCategoryData(0, $bSubmit);
                    if ($bSubmit) {

                        if ($this->errors->no('blog.admin.category.submit',array('id'=>0,'data'=>&$aData))) {
                            $this->model->categorySave(0, $aData);
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

                        $aData = $this->validateCategoryData($nCategoryID, $bSubmit);
                        if ($this->errors->no('blog.admin.category.submit',array('id'=>$nCategoryID,'data'=>&$aData))) {
                            $this->model->categorySave($nCategoryID, $aData);
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

                    $aResponse['form'] = $this->viewPHP($aData, 'admin.categories.form');
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

            if ($aResponse !== false && Request::isAJAX()) {
                $this->ajaxResponseForm($aResponse);
            }
        }

        $f = array();

        # формируем фильтр списка категорий
        $aData['list'] = $this->model->categoriesListing(array('pid' => 1));
        $aData['list'] = $this->viewPHP($aData, 'admin.categories.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array('list' => $aData['list']));
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

        if (Request::isPOST()) {

            $nNextCategoryID = $this->input->post('next', TYPE_UINT);
            if ($nNextCategoryID > 0) {
                # проверяем наличие категории
                $aDataNext = $this->model->categoryData($nNextCategoryID);
                if (empty($aDataNext) || $nNextCategoryID == $nCategoryID) {
                    $this->adminRedirect(Errors::IMPOSSIBLE, 'categories');
                }

                # перемещаем посты
                $bResult = $this->model->postsMoveToCategory($nNextCategoryID, $nCategoryID);
                if (!empty($bResult)) {
                    # удаляем категорию
                    $this->model->categoryDelete($nCategoryID);
                }
            } else {
                if (!$aData['posts']) {
                    # удаляем категорию
                    $this->model->categoryDelete($nCategoryID);
                }
            }

            $this->adminRedirect(Errors::SUCCESS, 'categories');
        }

        $aData['categories'] = $this->model->categoriesOptions(0, _t('','Выбрать'));

        return $this->viewPHP($aData, 'admin.categories.delete');
    }

    /**
     * Обрабатываем параметры запроса
     * @param integer $nPostID ID поста или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @param bff\db\Publicator $oPublicator или FALSE
     * @return array параметры
     */
    protected function validatePostData($nPostID, $bSubmit, $oPublicator = false)
    {
        $aData = array();
        $this->input->postm_lang($this->model->langPosts, $aData);
        $this->input->postm(array(
                'cat_id'    => TYPE_UINT, # Категория
                'content'   => TYPE_ARRAY, # Публикатор
                'enabled'   => TYPE_BOOL, # Включен
                'mtemplate' => TYPE_BOOL, # Использовать общий шаблон SEO
            ), $aData
        );

        if ($bSubmit) {
            # Категория
            if (!$aData['cat_id'] && static::categoriesEnabled()) {
                $this->errors->set(_t('','Выберите категорию'));
            }

            # Публикатор
            if (!empty($oPublicator) && $this->errors->no()) {
                $aDataPublicator = $oPublicator->dataPrepare($aData['content'], $nPostID);
                $aData['content'] = $aDataPublicator['content'];
                $aData['content_search'] = $aDataPublicator['content_search'];
            }
        } else {
            if (!$nPostID) {
                $aData['mtemplate'] = 1;
            }
        }

        return $aData;
    }

    /**
     * Валидация данных категории
     * @param integer $nCategoryID ID категории или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @return array параметры
     */
    protected function validateCategoryData($nCategoryID, $bSubmit)
    {
        $aData = array();
        $this->input->postm_lang($this->model->langCategories, $aData);
        $this->input->postm(array(
                'keyword'   => TYPE_NOTAGS, # URL-Keyword
                'enabled'   => TYPE_BOOL, # Включен
                'mtemplate' => TYPE_BOOL, # Использовать базовый шаблон SEO
            ), $aData
        );

        if ($bSubmit) {
            $aData['pid'] = $this->model->categoryRootID();
            # URL-Keyword
            $aData['keyword'] = $this->db->getKeyword($aData['keyword'], $aData['title'][LNG], TABLE_BLOG_CATEGORIES, $nCategoryID, 'keyword', 'id');
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
                bff::hook('blog.admin.ajax.default.action', $action, $this);
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