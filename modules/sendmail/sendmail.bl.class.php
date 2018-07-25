<?php

abstract class SendmailBase_ extends SendmailModule
{
    # статусы
    const STATUS_SCHEDULED      = 2;
    const STATUS_PROCESSING     = 3;
    const STATUS_PAUSE_BEGIN    = 4;
    const STATUS_PAUSED         = 5;
    const STATUS_CANCEL         = 6;
    const STATUS_FINISHED       = 7;

    /** @var SendmailModel */
    public $model = null;

    public function init()
    {
        parent::init();

        $this->aTemplates['sendmail_massend'] = array(
            'title'       => _t('sendmail','Почта: массовая рассылка писем'),
            'description' => _t('sendmail','Уведомление, отправляемое при массовой рассылке'),
            'vars'        => array(
                '{msg}' => _t('sendmail','Текст письма')
            ),
            'impl'        => true,
            'priority'    => 1000,
            'enotify'     => Users::ENOTIFY_NEWS,
        );
    }

    /**
     * Задержка перед стартом рассылки
     * @return int кол-во минут
     */
    public static function delay()
    {
        $delay = config::sys('sendmail.massend.delay', 3, TYPE_UINT);
        if ($delay <= 0 || $delay > 1440) {
            $delay = 3;
        }
        return $delay;
    }

    /**
     * Список статусов рассылки
     * @return array
     */
    public static function statuses()
    {
        $data = array(
            static::STATUS_SCHEDULED    => array('t' => _t('sendmail', 'Запланирована')),
            static::STATUS_PROCESSING   => array('t' => _t('sendmail', 'Выполняется')),
            static::STATUS_PAUSE_BEGIN  => array('t' => _t('sendmail', 'На паузе')),
            static::STATUS_PAUSED       => array('t' => _t('sendmail', 'На паузе')),
            static::STATUS_CANCEL       => array('t' => _t('sendmail', 'Завершена')),
            static::STATUS_FINISHED     => array('t' => _t('sendmail', 'Завершена')),
        );
        foreach ($data as $k => &$v) {
            $v['id'] = $k;
        } unset($v);
        return $data;
    }
    
    /**
     * Получение тегов начала и конца макроса
     * @return array
     */
    public function getTags()
    {
        return array($this->tagStart, $this->tagEnd);
    }

}