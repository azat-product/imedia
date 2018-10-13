<?php

class Blog_ extends BlogBase
{
    public function init()
    {
        parent::init();

        if (bff::$class == $this->module_name) {
            bff::setActiveMenu('//blog');
        }
    }

    /**
     * Блок последних постов на главной
     * @param array $opts параметры
     * @return string|array HTML
     */
    public function indexLastBlock($opts = array())
    {
        # доп. параметры:
        func::array_defaults($opts, array(
            'dataOnly'  => false,
            'category'  => 0,
            'tag'       => 0,
            'limit'     => 0,
            'preview'   => 0,
            'title'     => '',
        ));
        $dataOnly = $opts['dataOnly'];
        $limit = $opts['limit'];

        if (!$limit) {
            $limit = config::sysAdmin('blog.last.block.limit', 3, TYPE_UINT);
        }
        if (!$limit) {
            if ($dataOnly) return array();
            return '';
        }
        if (!$opts['preview']) {
            $opts['preview'] = config::sysAdmin('blog.last.block.preview', false, TYPE_BOOL);
        }

        $filter = array();
        if ($opts['category']) {
            $filter['cat_id'] = $opts['category'];
        }
        if ($opts['tag']) {
            $filter['tag'] = $opts['tag'];
        }

        $opts['list'] = $this->model->postsList($filter, false, ' LIMIT '.$limit);

        if ($dataOnly) {
            return $opts['list'];
        }
        if (empty($opts['list'])) return '';
        return $this->viewPHP($opts, 'index.last.block');
    }

    /**
     * Список постов
     */
    public function listing()
    {
        $pageSize = config::sysAdmin('blog.list.pagesize', 8, TYPE_UINT);
        $breadCrumbs = array(
            array('title' => _t('blog', 'Блог'), 'link' => static::url('index'), 'active' => false),
        );

        # cписок в категории
        $catKey = $this->input->get('cat', TYPE_NOTAGS);
        $catKey = trim($catKey, ' /\\');
        if (!empty($catKey)) {
            return $this->listingCategory($catKey);
        }

        # посты
        $page = 1;
        $pageLast = 1;
        $filter = array();
        $data['total'] = $this->model->postsList($filter, true);
        if ($data['total']) {
            $pgn = new Pagination($data['total'], $pageSize, array(
                'link'  => static::url('index'),
                'query' => array('page' => $page),
            ));
            $data['page'] = $page = $pgn->getCurrentPage();
            $pageLast = $pgn->getPageLast();
            $data['list'] = $this->model->postsList($filter, false, $pgn->getLimitOffset());
            $data['list'] = $this->viewPHP($data, 'list');
            $data['pgn'] = $pgn->view(array(), tpl::PGN_COMPACT);
        } else {
            $data['list'] = $data['pgn'] = ''; $data['page'] = 0;
        }

        # SEO: Список
        $this->urlCorrection(static::url('index'));
        $this->seo()->canonicalUrl(static::url('index', array(), true), array('page' => $page),
            array('page-current' => $page, 'page-last' => $pageLast)
        );
        $this->setMeta('listing', array('page' => $page), $data);

        # хлебные крошки
        $breadCrumbs[key($breadCrumbs)]['active'] = true;
        $data['breadCrumbs'] = & $breadCrumbs;

        # блок справа
        $data['rightBlock'] = $this->listingRightBlock();

        return $this->viewPHP($data, 'list.index');
    }

    /**
     * Список постов в категории
     */
    protected function listingCategory($catKey)
    {
        if (!static::categoriesEnabled()) {
            $this->redirect(static::url('index'));
        }

        $pageSize = config::sysAdmin('blog.list-category.pagesize', 8, TYPE_UINT);
        $breadCrumbs = array(
            array('title' => _t('blog', 'Блог'), 'link' => static::url('index'), 'active' => false),
        );

        $data = $this->model->categoryView($catKey);
        if (empty($data)) {
            $this->errors->error404();
        }

        # формируем корректный url
        $catUrl = static::url('cat', array('keyword' => $catKey));

        # посты в категории
        $page = 1;
        $filter = array('cat_id' => $data['id']);
        $data['total'] = $this->model->postsList($filter, true);
        if ($data['total']) {
            $pgn = new Pagination($data['total'], $pageSize, array(
                'link'  => $catUrl,
                'query' => array('page' => $page),
            ));
            $page = $pgn->getCurrentPage();
            $data['list'] = $this->model->postsList($filter, false, $pgn->getLimitOffset());
            $data['list'] = $this->viewPHP($data, 'list');
            $data['pgn'] = $pgn->view(array(), tpl::PGN_COMPACT);
        } else {
            $data['list'] = $data['pgn'] = '';
        }

        # SEO: Список в категории
        $this->urlCorrection($catUrl);
        $this->seo()->robotsIndex(false);
        $this->setMeta('listing-category', array(
                'page'     => $page,
                'category' => $data['title'],
            ), $data
        );

        # хлебные крошки
        $breadCrumbs[] = array('title' => $data['title'], 'link' => $catUrl, 'active' => $page <= 1);
        $data['breadCrumbs'] = & $breadCrumbs;

        # блок справа
        $data['rightBlock'] = $this->listingRightBlock($data['id'], 0);

        return $this->viewPHP($data, 'list.category');
    }

    /**
     * Список постов по тегу
     */
    public function listingTag()
    {
        if (!static::tagsEnabled()) {
            $this->redirect(static::url('index'));
        }

        $pageSize = config::sysAdmin('blog.list-tag.pagesize', 8, TYPE_UINT);
        $breadCrumbs = array(
            array('title' => _t('blog', 'Блог'), 'link' => static::url('index'), 'active' => false),
        );

        $tagID = $this->input->get('tag', TYPE_UINT);

        $data = $this->postTags()->tagData($tagID);
        if (empty($data)) {
            $this->errors->error404();
        }

        # формируем корректный url
        $tagUrl = static::url('tag', array('tag' => $data['tag'], 'id' => $tagID));
        $data['tag'] = tpl::ucfirst($data['tag']);

        # посты по тегу
        $page = 1;
        $filter = array('tag' => $tagID);
        $data['total'] = $this->model->postsList($filter, true);
        if ($data['total']) {
            $pgn = new Pagination($data['total'], $pageSize, array(
                'link'  => $tagUrl,
                'query' => array('page' => $page),
            ));
            $page = $pgn->getCurrentPage();
            $data['list'] = $this->model->postsList($filter, false, $pgn->getLimitOffset());
            $data['list'] = $this->viewPHP($data, 'list');
            $data['pgn'] = $pgn->view(array(), tpl::PGN_COMPACT);
        } else {
            $data['list'] = $data['pgn'] = '';
        }

        # SEO: Список по тегу
        $this->urlCorrection($tagUrl);
        $this->seo()->robotsIndex(false);
        $this->setMeta('listing-tag', array(
                'page' => $page,
                'tag'  => $data['tag'],
            )
        );

        # хлебные крошки
        $breadCrumbs[] = array('title' => HTML::escape($data['tag']), 'link' => $tagUrl, 'active' => $page <= 1);
        $data['breadCrumbs'] = & $breadCrumbs;

        # блок справа
        $data['rightBlock'] = $this->listingRightBlock(0, $tagID);

        return $this->viewPHP($data, 'list.tag');
    }

    /**
     * Правый блок в списке постов
     */
    protected function listingRightBlock($categoryID = 0, $tagID = 0)
    {
        $data = array();

        if (static::categoriesEnabled()) {
            $data['categories'] = $this->model->categoriesList();
            foreach ($data['categories'] as $k => &$v) {
                if (empty($v['posts'])) {
                    unset($data['categories'][$k]);
                    continue;
                }
                $v['link'] = static::url('cat', array('keyword' => $v['keyword']));
                $v['active'] = ($v['id'] == $categoryID);
            }
            unset($v);
        }

        if (static::tagsEnabled()) {
            $data['tags'] = $this->postTags()->tagsCloud(12, NULL, config::sysAdmin('blog.tags-cloud.limit', 0, TYPE_UINT));
            foreach ($data['tags'] as $k => &$v) {
                if (empty($v['items'])) {
                    unset($data['tags'][$k]);
                    continue;
                }
                $v['link'] = static::url('tag', array('tag' => $v['tag'], 'id' => $v['id']));
                $v['active'] = ($v['id'] == $tagID);
            }
            unset($v);
        }

        $data['favs'] = $this->model->postsList(array('fav' => 1));
        foreach ($data['favs'] as &$v) {
            $v['link'] = static::urlDynamic($v['link']);
        }
        unset($v);

        return $this->viewPHP($data, 'list.rightblock');
    }

    /**
     * Просмотр поста
     */
    public function view()
    {
        $postID = $this->input->get('id', TYPE_UINT);
        if (!$postID) {
            $this->errors->error404();
        }

        $data = $this->model->postView($postID);
        if (empty($data)) {
            $this->errors->error404();
        }

        # Last Modified
        if (!BFF_DEBUG) {
            Request::lastModified($data['modified']);
        }

        # следующий пост
        $data['next'] = $this->model->postNext($postID, $data['created']);
        if (!empty($data['next'])) {
            $data['next']['link'] = static::urlDynamic($data['next']['link']);
        }

        # теги
        $data['tags_meta'] = array();
        if (static::tagsEnabled()) {
            $data['tags'] = $this->postTags()->tagsGet($postID);
            foreach ($data['tags'] as &$v) {
                $v['link'] = static::url('tag', array('tag' => $v['tag'], 'id' => $v['id']));
                $data['tags_meta'][] = $v['tag'];
            }
            unset($v);
        }

        # SEO: Просмотр поста
        $this->urlCorrection(static::urlDynamic($data['link']));
        $this->seo()->canonicalUrl($data['link'], array());
        $this->setMeta('view', array(
                'title'     => $data['title'],
                'textshort' => tpl::truncate(strip_tags($data['textshort']), 150, '...'),
                'tags'      => (!empty($data['tags_meta']) ? join(', ', $data['tags_meta']) : ''),
            ), $data
        );
        # SEO: Open Graph
        $this->seo()->setSocialMetaOG($data['share_title'], $data['share_description'], array(), $data['link'], $data['share_sitename']);

        # хлебные крошки
        $breadCrumbs = array(
            array('title' => _t('blog', 'Блог'), 'link' => static::url('index'), 'active' => false),
            array('title' => $data['title'], 'link' => static::urlDynamic($data['link']), 'active' => true),
        );
        $data['breadCrumbs'] = & $breadCrumbs;

        # содержание
        $data['content'] = $this->initPublicator()->view($data['content'], $postID, 'view.content', $this->module_dir_tpl);

        # Блок поделиться
        $data['share_code'] = config::sysAdmin('blog.view.sharecode', config::get('blog_share_code', '', TYPE_STR), TYPE_STR);

        return $this->viewPHP($data, 'view');
    }

}