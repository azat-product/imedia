<?php

abstract class BlogBase_ extends Module
{
    /** @var BlogModel */
    public $model = null;
    public $securityKey = '75409335cd1659479b0d33fd462df2d9';

    public function init()
    {
        parent::init();
        $this->module_title = _t('blog','Блог');

        bff::autoloadEx(array(
            'BlogPostPreview' => array('app', 'modules/blog/blog.preview.php'),
            'BlogPostTags' => array('app', 'modules/blog/blog.post.tags.php'),
        ));
    }

    /**
     * Shortcut
     * @return Blog
     */
    public static function i()
    {
        return bff::module('blog');
    }

    /**
     * Shortcut
     * @return BlogModel
     */
    public static function model()
    {
        return bff::model('blog');
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        $url = $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # главная
            case 'index':
                $url .= '/blog/';
                break;
            # список постов по категории
            case 'cat':
                $url .= '/blog/' . $opts['keyword'] . '/';
                break;
            # список постов по тегу
            case 'tag':
                $url .= '/blog/tag/' . HTML::escape($opts['tag']) . '-' . $opts['id'];
                break;
            # просмотр поста
            case 'view':
                $url .= '/blog/' . mb_substr(mb_strtolower(func::translit($opts['title'])), 0, 100) . '-' . $opts['id'] . '.html';
                break;
        }
        return bff::filter('blog.url', $url, array('key'=>$key, 'opts'=>$opts, 'dynamic'=>$dynamic, 'base'=>$base));
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        $templates = array(
            'pages'  => array(
                'listing'          => array(
                    't'      => _t('seo','Список'),
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(),
                    'fields'  => array(
                        'titleh1' => array(
                            't'    => _t('seo','Заголовок H1'),
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => _t('seo','SEO текст'),
                            'type' => 'wy',
                        ),
                    ),
                ),
                'listing-category' => array(
                    't'       => _t('blog','Список в категории'),
                    'list'    => true,
                    'i'       => true,
                    'macros'  => array(
                        'category' => array('t' => _t('blog','Название категории')),
                    ),
                    'inherit' => true,
                ),
                'listing-tag'      => array(
                    't'      => _t('blog','Список по тегу'),
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'tag' => array('t' => _t('blog','Тег')),
                    )
                ),
                'view'             => array(
                    't'       => _t('blog','Просмотр поста'),
                    'list'    => false,
                    'i'       => true,
                    'macros'  => array(
                        'title'     => array('t' => _t('blog','Заголовок поста')),
                        'textshort' => array('t' => _t('blog','Краткое описание (до 150 символов)')),
                        'tags'      => array('t' => _t('blog','Список тегов')),
                    ),
                    'fields' => array(
                        'share_title'       => array(
                            't'    => _t('seo','Заголовок (поделиться в соц. сетях)'),
                            'type' => 'text',
                        ),
                        'share_description' => array(
                            't'    => _t('seo','Описание (поделиться в соц. сетях)'),
                            'type' => 'textarea',
                        ),
                        'share_sitename'    => array(
                            't'    => _t('seo','Название сайта (поделиться в соц. сетях)'),
                            'type' => 'text',
                        ),
                    ),
                    'inherit' => true,
                ),
            ),
            'macros' => array(),
        );

        if (!static::categoriesEnabled()) {
            unset($templates['pages']['listing-category']);
            unset($templates['pages']['view']['category']);
        }
        if (!static::tagsEnabled()) {
            unset($templates['pages']['listing-tag']);
            unset($templates['pages']['view']['tags']);
        }

        return $templates;
    }

    /**
     * Инициализируем компонент bff\db\Publicator
     * @return bff\db\Publicator компонент
     */
    public function initPublicator()
    {
        $aSettings = array(
            'title'           => false,
            'langs'           => $this->locale->getLanguages(),
            'images_path'     => bff::path('blog', 'images'),
            'images_path_tmp' => bff::path('tmp', 'images'),
            'images_url'      => bff::url('blog', 'images'),
            'images_url_tmp'  => bff::url('tmp', 'images'),
            // gallery
            'gallery_sz_view' => array(
                'width'    => 750,
                'height'   => false,
                'vertical' => array('width' => false, 'height' => 640),
                'quality'  => 95,
                'sharp'    => array()
            ), // no sharp
        );

        return $this->attachComponent('publicator', new bff\db\Publicator($this->module_name, $aSettings));
    }

    /**
     * Инициализация компонента работы с тегами
     * @return BlogPostTags
     */
    public function postTags()
    {
        static $i;
        if (!isset($i)) {
            $i = new BlogPostTags();
        }

        return $i;
    }

    /**
     * Инициализация компонента работы с превью постов
     * @param integer $nPostID ID поста
     * @return BlogPostPreview объект
     */
    public function postPreview($nPostID = 0)
    {
        static $i;
        if (!isset($i)) {
            $i = new BlogPostPreview();
        }
        $i->setRecordID($nPostID);

        return $i;
    }

    /**
     * Включены ли категории
     * @return bool
     */
    public static function categoriesEnabled()
    {
        return config::sysAdmin('blog.categories', true, TYPE_BOOL);
    }

    /**
     * Включены ли теги
     * @return bool
     */
    public static function tagsEnabled()
    {
        return config::sysAdmin('blog.tags', true, TYPE_BOOL);
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('blog', 'images') => 'dir', # изображения объявлений
            bff::path('tmp', 'images')  => 'dir', # tmp
        ));
    }

}