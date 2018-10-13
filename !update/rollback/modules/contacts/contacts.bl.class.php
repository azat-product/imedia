<?php

abstract class ContactsBase_ extends Module
{
    /** @var ContactsModel */
    var $model = null;

    # Типы контактов:
    const TYPE_SITE_ERROR = 1;
    const TYPE_TECH_SUPPORT = 2;
    const TYPE_OTHER = 4;

    public function init()
    {
        parent::init();
        $this->module_title = _t('contacts','Контакты');
    }

    /**
     * Shortcut
     * @return Contacts
     */
    public static function i()
    {
        return bff::module('contacts');
    }

    /**
     * Shortcut
     * @return ContactsModel
     */
    public static function model()
    {
        return bff::model('contacts');
    }

    public function sendmailTemplates()
    {
        return array(
            'contacts_admin' => array(
                'title'       => _t('contacts','Форма контактов: уведомление о новом сообщении'),
                'description' => _t('contacts','Уведомление, отправляемое <u>администратору</u> ([mail]) после отправки сообщения через форму контактов', array('mail'=>config::sys('mail.admin'))),
                'vars'        => array('{name}' => _t('contacts','Имя'), '{email}' => _t('', 'Email'), '{message}' => _t('contacts', 'Сообщение'))
            ,
                'impl'        => true,
                'priority'    => 100,
                'enotify'     => -1,
            ),
        );
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
            # форма контактов
            case 'form':
                $url .= '/contact/';
                break;
        }
        return bff::filter('contacts.url', $url, array('key'=>$key, 'opts'=>$opts, 'dynamic'=>$dynamic, 'base'=>$base));
    }

    /**
     * Типы контактов
     * @param bool $options true - в виде HTML::options, false - в виде массива
     * @return array|string
     */
    protected function getContactTypes($options = false)
    {
        $types = bff::filter('contacts.types.list', array(
            self::TYPE_SITE_ERROR => array(
                'title' => _t('contacts', 'Ошибка на сайте'),
            ),
            self::TYPE_TECH_SUPPORT => array(
                'title' => _t('contacts', 'Технический вопрос'),
            ),
            self::TYPE_OTHER => array(
                'title' => _t('contacts', 'Другие вопросы'),
                'priority' => 1000,
            ),
        ));

        foreach ($types as $k=>&$v) {
            if (!is_array($v)) {
                $v = array('title'=>$v);
            }
            if (!isset($v['id'])) {
                $types[$k]['id'] = $k;
            }
        } unset($v);
        func::sortByPriority($types, 'priority', true);

        if ($options) {
            return HTML::selectOptions($types, 0, false, 'id', 'title');
        }

        return $types;
    }

    /**
     * Обновление счетчика новый сообщений, отправленных через форму
     * @param integer $nTypeID ID типа сообщения
     * @param integer $nIncrement
     */
    protected function updateCounter($nTypeID, $nIncrement)
    {
        config::saveCount('contacts_new', $nIncrement, true);
        config::saveCount('contacts_new_' . $nTypeID, $nIncrement, true);
    }

    /**
     * Пересчет всех счетчиков
     */
    protected function countersRefresh()
    {
        $newTotal = $this->model->contactsListing(array('viewed'=>0), true);
        config::save('contacts_new', $newTotal, true);

        $types = $this->getContactTypes();
        foreach ($types as $k=>$v) {
            $newType = 0;
            if ($newTotal > 0) {
                $newType = $this->model->contactsListing(array('viewed' => 0, 'ctype' => $k), true);
            }
            config::save('contacts_new_'.$k, $newType, true);
        }
    }

}