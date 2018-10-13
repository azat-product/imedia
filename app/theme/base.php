<?php

/**
 * Базовая тема приложения
 * @version 0.3
 */
class Theme extends bff\extend\theme\Base
{
    protected $siteIndexTemplates = array();

    public function init()
    {
        parent::init();

        # Logo:
        $this->siteLogo([
            'header' => [
                'title' => _t('site','Логотип #1'),
                'tip' => _t('site','логотип в шапке сайта: [sizes]', ['sizes'=>'260px x 53px']),
                'description' => _t('site','Допустимые форматы: [formats]', ['formats'=>'pgn, jpg']),
            ],
            'header.short' => [
                'title' => _t('site','Логотип #2'),
                'tip' => _t('site','логотип на странице авторизации, регистрации и подобных'),
                'description' => _t('site','Допустимые форматы: [formats]', ['formats'=>'pgn, jpg']),
            ],
        ]);

        # Favicon:
        $this->siteFavicon([
            'icon' => [
                'title'   => _t('site','Favicon'),
                'tip'     => _t('site','16x16 или 32x32 или 196x196'),
                'description' => _t('site','Допустимые форматы: [formats]', ['formats'=>'pgn, svg, ico']),
                'attr'    => ['rel'=>'icon'],
                'file'  => [
                    'extensionsAllowed' => 'png,svg,ico',
                    'maxSize' => 3145728,
                ],
            ],
            'apple-touch' => [
                'title'   => _t('site','Apple Touch icon'),
                'tip'     => '180x180',
                'description' => _t('site','Допустимые форматы: [formats]', ['formats'=>'pgn']),
                'attr'    => ['rel'=>'apple-touch-icon-precomposed'],
                'file'  => [
                    'extensionsAllowed' => 'png',
                    'maxSize' => 3145728,
                ],
            ],
        ]);

        # Site Index Templates:
        $this->configSettingsTemplateRegister('site.index.templates', [
            'template' => [
                'title' => _t('site','Внешний вид главной'),
                'type' => TYPE_STR,
                'input' => 'select',
                'default' => 'index.default',
                'priority' => 1,
                'options' => function() {
                    return \Site::indexTemplates($this->siteIndexTemplates);
                },
            ],
        ], function(){}, [
            'default' => [
                'config.key' => 'site.index.template',
            ],
        ]);

        if ($this->isBaseTheme()) {
            $this->configSettings([]);
            $this->cssEdit([
                static::CSS_FILE_MAIN => [
                    'path' => PATH_PUBLIC.'css'.DS.'main.css', 'save' => false,
                ],
                static::CSS_FILE_CUSTOM => [
                    'path' => PATH_PUBLIC.'css'.DS.'custom.css', 'save' => 'custom',
                    'path_custom' => PATH_PUBLIC.'custom'.DS.'css'.DS.'custom.css',
                ],
            ]);
        }
    }

    /**
     * Список внешних видов центральной части главной страницы реализуемых темой
     * @param array $implemented [
     *      'ключ' => [
     *         'title' => 'Название', // название внешнего вида
     *         'map' => true/false, // требуется инициализация карты
     *         'regions' => true/false, // требуется инициализация списка регионов
     *         'file' => 'index.default' // название php файла расположенного в директории /modules/site/tpl/def/ темы
     *     ],
     * ]
     */
    public function siteIndexTemplates(array $implemented)
    {
        $this->siteIndexTemplates = $implemented;
    }
}