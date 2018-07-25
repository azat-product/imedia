<?php

class Theme_Imedia_t12d0f3 extends Theme
{
    public function init()
    {
        parent::init();

        $this->setSettings(array(
            'theme_title'   => 'imedia',
            'theme_version' => '1.0.0',
            'extension_id'  => 't12d0f311a5482997ff68b04ef75c87f43a77617',
        ));

        /**
         * Настройки заполняемые в админ. панели
         */
        $this->configSettings(array(
            'device.desktop.responsive' => array(
                'input' => 'sys',
                'default' => false,
            ),
            'site.index.template' => array(
                'title' => _t('site','Внешний вид главной'),
                'type' => TYPE_STR,
                'input' => 'select',
                'default' => 'index.default',
                'options' => function() {
                    return array(
                        'index.default' => array('id' => 0, 'title' => 'По-умолчанию', 'tpl' => 'index.default', 'map' => false, 'regions' => false),
                        'index.regions' => array('id' => 1, 'title' => 'С регионами', 'tpl' => 'index.regions', 'map' => false, 'regions' => true),
                        'index.map1'    => array('id' => 2, 'title' => 'С картой', 'tpl' => 'index.map1', 'map' => true, 'regions' => true)
                    );
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
        # ignore-start
        bff::hooksBulk([
            'extensions.themes.'.$this->getName().'.settings.tabs' => function($tabs){
                $tabs['copy-start'] = array(
                    'title' => 'Копирование',
                    'priority' => 2,
                );
                return $tabs;
            },
            'extensions.themes.'.$this->getName().'.settings.tabs.content' => function($data){
                $data = array();
                echo $this->viewPHP($data, 'ignore/copy.form');
            },
            'extensions.themes.'.$this->getName().'.settings.submit' => function($data){
                $isSubmit = $this->input->postget('copy_submit', TYPE_UINT);
                if (empty($isSubmit)) { return; }
                $response = array();
                do {
                    if (!is_writable(PATH_THEMES)) {
                        $this->errors->set('Недостаточно прав для записи в директорию "[dir]"', ['dir'=>str_replace(PATH_BASE,DS,PATH_THEMES)]);
                        break;
                    }
                    $title = $this->input->post('copy_title', TYPE_TEXT);
                    $name = $this->input->post('copy_name', TYPE_NOTAGS);
                    $type = $this->getExtensionType();
                    \bff::dev()->createExtension($title, $name, $type, $this);
                    $response['redirect'] = tplAdmin::adminLink(bff::$event.'&type='.$type);
                } while(false);
                $this->ajaxResponseForm($response);
            }
        ]);
        # ignore-stop
    }

    /**
     * Запуск темы
     */
    protected function start()
    {
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