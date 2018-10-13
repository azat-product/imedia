<?php

class Help_ extends HelpBase
{
    public function init()
    {
        parent::init();

        if (bff::$class == $this->module_name) {
            bff::setActiveMenu('//help');
        }
    }

    /**
     * Список вопросов
     */
    public function listing()
    {
        $data = array();
        $breadCrumbs = array(
            array('title' => _t('help', 'Помощь'), 'link' => static::url('index'), 'active' => false),
        );
        $catKey = $this->input->get('cat', TYPE_NOTAGS);
        $catKey = trim($catKey, ' /');
        if (!empty($catKey)) {
            $data = $this->model->categoryView($catKey);
            if (!empty($data)) {
                # seo: Список в категории
                $this->urlCorrection(static::url('cat', array('keyword' => $catKey)));
                $this->seo()->canonicalUrl(static::url('cat', array('keyword' => $catKey), true));
                $metaCategories = array();
                foreach ($data['crumbs'] as $v) {
                    $metaCategories[] = $v['title'];
                }
                $this->setMeta('listing-category', array(
                        'category'           => $data['title'],
                        'categories'         => join(', ', $metaCategories),
                        'categories.reverse' => join(', ', array_reverse($metaCategories, true)),
                    ), $data
                );

                # вопросы или подкатегории
                if (!empty($data['questions_list'])) {
                    foreach ($data['questions_list'] as &$v) {
                        $v['link'] = static::urlDynamic($v['link']);
                    }
                    unset($v);
                } else {
                    if (!empty($data['subcats_list'])) {
                        foreach ($data['subcats_list'] as &$v) {
                            $v['link'] = static::url('cat', array('keyword' => $v['keyword']));
                        }
                        unset($v);
                    }
                }

                # хлебные крошки
                foreach ($data['crumbs'] as &$v) {
                    $breadCrumbs[] = array('title'  => $v['title'],
                                           'link'   => static::url('cat', array('keyword' => $v['keyword'])),
                                           'active' => false
                    );
                }
                unset($v);
                $breadCrumbs[sizeof($breadCrumbs) - 1]['active'] = true;
                $data['breadCrumbs'] = & $breadCrumbs;

                return $this->viewPHP($data, 'list.category');
            } else {
                $this->errors->error404();
            }
        }

        # SEO: Главная
        $this->urlCorrection(static::url('index'));
        $this->seo()->canonicalUrl(static::url('index', array(), true));
        $this->setMeta('listing', array(), $data);

        # категории / подкатегории (или вопросы)
        $data['items'] = $this->model->categoriesListIndex();

        # частые вопросы
        $data['favs'] = $this->model->questionsFav();

        # хлебные крошки
        $breadCrumbs[key($breadCrumbs)]['active'] = true;
        $data['breadCrumbs'] = & $breadCrumbs;

        return $this->viewPHP($data, 'list.index');
    }

    /**
     * Поиск вопросов
     */
    public function search()
    {
        $pageSize = config::sysAdmin('help.search.pagesize', 10, TYPE_UINT);
        $f = $this->searchFormData();
        if (empty($f['q']) || mb_strlen($f['q']) < 3) {
            $this->redirect(static::url('index'));
        }

        $data['total'] = $this->model->questionsSearch($f['q'], true);
        $page = 1;
        if ($data['total']) {
            $pgn = new Pagination($data['total'], $pageSize,
                static::url('search', array('q' => $f['q'])) . '&page=' . Pagination::PAGE_ID);
            $data['questions'] = $this->model->questionsSearch($f['q'], false, $pgn->getLimitOffset());
            if (!empty($data['questions'])) {
                foreach ($data['questions'] as &$v) {
                    $v['link'] = static::urlDynamic($v['link']);
                    $v['title'] = strtr($v['title'], array($f['q'] => '<em>' . $f['q'] . '</em>'));
                    $v['textshort'] = strtr($v['textshort'], array($f['q'] => '<em>' . $f['q'] . '</em>'));
                }
                unset($v);
            }
            $page = $pgn->getCurrentPage();
            $data['pgn'] = $pgn->view(array('pageto' => false, 'arrows' => false));
            $data['num'] = ($f['page'] <= 1 ? 1 : (($f['page'] - 1) * $pageSize) + 1);
        } else {
            $data['pgn'] = '';
        }

        $data['f'] = & $f;
        $data['breadCrumbs'] = array(
            array('title' => _t('help', 'Помощь'), 'link' => static::url('index'), 'active' => true),
        );

        # SEO: Поиск вопроса
        $this->seo()->robotsIndex(false);
        $this->setMeta('search', array(
                'query' => $f['q'],
                'page'  => $page,
            )
        );

        return $this->viewPHP($data, 'list.search');
    }

    /**
     * Поиск вопросов: форма поиска
     */
    public function searchForm()
    {
        $data['f'] = $this->searchFormData();

        return $this->viewPHP($data, 'search.form');
    }

    /**
     * Поиск вопросов: данные для формы поиска
     */
    public function searchFormData(&$dataUpdate = false)
    {
        static $data;
        if (isset($data)) {
            if ($dataUpdate !== false) {
                $data = $dataUpdate;
            }

            return $data;
        }

        # поисковая строка
        $data['q'] = $this->input->postget((DEVICE_DESKTOP_OR_TABLET ? 'q' : 'q_m'), TYPE_NOTAGS);
        $data['q'] = $this->input->cleanSearchString($data['q'], 80);

        # страница
        $data['page'] = $this->input->postget('page', TYPE_UINT);
        if (!$data['page']) {
            $data['page'] = 1;
        }

        return $data;
    }

    /**
     * Просмотр вопросов
     */
    public function view()
    {
        # данные о вопросе
        $questionID = $this->input->get('id', TYPE_UINT);
        if (!$questionID) {
            $this->errors->error404();
        }
        $data = $this->model->questionView($questionID);
        if (empty($data)) {
            $this->errors->error404();
        }
        View::setPageData([
            'help_view_id' => $questionID,
        ]);

        # SEO: Просмотр вопроса
        $this->urlCorrection(static::urlDynamic($data['link']));
        $this->seo()->canonicalUrl($data['link']);
        $metaCategories = array();
        foreach ($data['crumbs'] as $v) {
            $metaCategories[] = $v['title'];
        }
        $this->setMeta('view', array(
                'title'              => $data['title'],
                'textshort'          => tpl::truncate(strip_tags($data['textshort']), 150),
                'category'           => (!empty($metaCategories) ? end($metaCategories) : ''),
                'categories'         => (!empty($metaCategories) ? join(', ', $metaCategories) : ''),
                'categories.reverse' => (!empty($metaCategories) ? join(', ', array_reverse($metaCategories, true)) : ''),
            ), $data
        );

        # другие вопросы
        if (!empty($data['questions_other'])) {
            foreach ($data['questions_other'] as &$v) {
                $v['link'] = static::urlDynamic($v['link']);
            }
            unset($v);
        }

        # хлебные крошки
        $breadCrumbs = array(
            array('title' => _t('help', 'Помощь'), 'link' => static::url('index'), 'active' => false),
        );
        foreach ($data['crumbs'] as &$v) {
            $breadCrumbs[] = array(
                'title'  => $v['title'],
                'link'   => static::url('cat', array('keyword' => $v['keyword'])),
                'active' => false,
            );
        }
        unset($v, $data['crumbs']);
        $data['breadCrumbs'] = & $breadCrumbs;

        # содержание
        $data['content'] = $this->initPublicator()->view($data['content'], $questionID, 'view.content', $this->module_dir_tpl);

        return $this->viewPHP($data, 'view');
    }

    /**
     * Обрабатываем событие изменения порядка формирования ссылок
     * @param array $params [changed=>prefix.list]
     */
    public function cronChangeUrls(array $params)
    {
        if (!bff::cron() || empty($params['changed'])) return;

        if ($params['changed'] === 'prefix.list') {
            $this->model->questionsUrlRefresh($this->locale->getDefaultLanguage());
        }
    }
}