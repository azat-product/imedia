<?php

class Plugin_Counters_p0f5c88 extends Plugin
{
    public function init()
    {
        parent::init();

        $this->setSettings(array(
            'plugin_title'   => 'Счетчик объявлений в строке поиска',
            'plugin_version' => '1.0.3',
            'extension_id'   => 'p0f5c88bbcbe0702d4218ef89b5af4921242f7f3',
        ));

        /**
         * Настройки заполняемые в админ. панели
         */
        $this->configSettings(array(

        ));
    }

    protected function start()
    {
        $this->css('css/counters_styles.css');
        bff::hookAdd('counters.catData', function($catData) {
            $catData['items'] = BBS::model()->catsItemsCounters(array(
                'pid' => BBS::CATS_ROOTID), 0
            );
            return $catData;
        });
    }
}