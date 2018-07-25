<?php

class Theme_Custom extends Theme
{
    public function init()
    {
        parent::init();

        $this->setSettings(array(
            'theme_title'   => 'Доработки',
            'theme_version' => '1.0.0',
        ));

        /**
         * Настройки заполняемые в админ. панели
         */
        $this->configSettings(array(
            'site.index.template' => array(
                'title' => _t('site','Внешний вид главной'),
                'type' => TYPE_STR,
                'input' => 'select',
                'default' => 'index.default',
                'options' => function() {
                    return \Site::indexTemplates();
                },
            ),
            'bbs.search.filter.vertical' => array(
                'title' => _t('bbs','Вертикальный фильтр в списках'),
                'input' => 'select',
                'type' => TYPE_BOOL,
                'default' => false,
                'options' => array(
                    true => array('title' => _t('','включено')),
                    false => array('title' => _t('','выключено')),
                ),
            ),
            'bbs.index.subcats.limit' => array(
                'title' => _t('bbs','Подкатегории на главной'),
                'description' => _t('bbs','Кол-во видимых подкатегорий на главной'),
                'input' => 'number',
                'default' => 5,
                'min' => 1,
                'max' => 1000,
            ),
            'bbs.index.last.limit' => array(
                'title' => _t('bbs','Блок объявлений на главной'),
                'description' => _t('bbs','Кол-во объявлений в блоке, [num] - скрыть блок', array('num'=>0)),
                'input' => 'number',
                'default' => 10,
                'min' => 0,
                'max' => 30,
            ),
            'logo' => array(
                'title' => 'Логотип #1',
                'tip' => 'логотип в шапке сайта',
                'input' => 'image',
                'image' => array(
                    'sizes' => array(
                        'view',
                    ),
                ),
            ),
            'logo.short' => array(
                'title' => 'Логотип #2',
                'tip' => 'логотип на странице авторизации, регистрации и подобных',
                'input' => 'image',
                'image' => array(
                    'sizes' => array(
                        'view',
                    ),
                ),
            ),
        ));
    }

    /**
     * Запуск темы
     */
    protected function start()
    {
        # CSS:
        $this->css('css/custom.css');
        # Логотипы:
        bff::hookAdd('site.logo.url.header', function($url){
            $logo = $this->configImages('logo', 'view');
            return (!empty($logo) ? $logo : $url);
        });
        bff::hookAdd('site.logo.url.header.short', function($url){
            $logo = $this->configImages('logo.short', 'view');
            return (!empty($logo) ? $logo : $url);
        });
    }
}