<?php

abstract class HelpBase_ extends Module
{
    /** @var HelpModel */
    public $model = null;
    public $securityKey = '7a6479879566d66524eeea5e38589b28';

    public function init()
    {
        parent::init();
        $this->module_title = _t('help','Помощь');
    }

    /**
     * Shortcut
     * @return Help
     */
    public static function i()
    {
        return bff::module('help');
    }

    /**
     * Shortcut
     * @return HelpModel
     */
    public static function model()
    {
        return bff::model('help');
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
        return bff::router()->url('help-'.$key, $opts, ['dynamic'=>$dynamic,'module'=>'help']);
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        return array(
            'pages'  => array(
                'listing'          => array(
                    't'      => _t('help', 'Главная'),
                    'list'   => false,
                    'i'      => true,
                    'macros' => array(),
                    'fields'  => array(
                        'titleh1' => array(
                            't'    => _t('seo', 'Заголовок H1'),
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => _t('seo', 'SEO текст'),
                            'type' => 'wy',
                        ),
                    ),
                ),
                'listing-category' => array(
                    't'       => _t('help', 'Список в категории'),
                    'list'    => false,
                    'i'       => true,
                    'macros'  => array(
                        'category'           => array('t' => _t('help', 'Название категории (текущая)')),
                        'categories'         => array('t' => _t('help', 'Название всех категорий')),
                        'categories.reverse' => array('t' => _t('help', 'Название всех категорий<br />(обратный порядок)')),
                    ),
                    'inherit' => true,
                ),
                'search'           => array(
                    't'      => _t('help', 'Поиск вопроса'),
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'query' => array('t' => _t('help', 'Строка поиска')),
                    ),
                ),
                'view'             => array(
                    't'       => _t('help', 'Просмотр вопроса'),
                    'list'    => false,
                    'i'       => true,
                    'macros'  => array(
                        'title'              => array('t' => _t('help', 'Заголовок вопроса')),
                        'textshort'          => array('t' => _t('help', 'Краткое описание (до 150 символов)')),
                        'category'           => array('t' => _t('help', 'Категория вопроса (текущая)')),
                        'categories'         => array('t' => _t('help', 'Название всех категорий')),
                        'categories.reverse' => array('t' => _t('help', 'Название всех категорий<br />(обратный порядок)')),
                    ),
                    'inherit' => true,
                ),
            ),
            'macros' => array(),
        );
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
            'images_path'     => bff::path('help', 'images'),
            'images_path_tmp' => bff::path('tmp', 'images'),
            'images_url'      => bff::url('help', 'images'),
            'images_url_tmp'  => bff::url('tmp', 'images'),
            # photo
            'photo_sz_view'   => array('width' => 800),
            # gallery
            'gallery_sz_view' => array(
                'width'    => 800,
                'height'   => false,
                'vertical' => array('width' => false, 'height' => 400),
                'quality'  => 95,
                'sharp'    => array() // no sharp
            ),
        );

        return $this->attachComponent('publicator', new bff\db\Publicator($this->module_name, $aSettings));
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('help', 'images') => 'dir', # изображения
            bff::path('tmp', 'images')  => 'dir', # tmp
        ));
    }
}