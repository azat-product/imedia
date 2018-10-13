<?php

abstract class InternalMailBase_ extends Module
{
    /** @var InternalMailModel */
    public $model = null;
    protected $securityKey = '258e6379ce805822c2c73b8c40de0a02';

    # системные папки
    const FOLDER_ALL = 0; # все
    const FOLDER_FAVORITE = 1; # избранные
    const FOLDER_IGNORE = 2; # игнорируемые
    const FOLDER_SH_USER = 4; # магазин: для магазина
    const FOLDER_SH_SHOP = 8; # магазин: для частного лица

    public function init()
    {
        parent::init();

        $this->module_title = _t('internalmail','Сообщения');

        bff::autoloadEx(array(
                'InternalMailAttachment' => array('app', 'modules/internalmail/internalmail.attach.php'),
            )
        );
    }

    /**
     * Shortcut
     * @return InternalMail
     */
    public static function i()
    {
        return bff::module('internalmail');
    }

    /**
     * Shortcut
     * @return InternalMailModel
     */
    public static function model()
    {
        return bff::model('internalmail');
    }

    public function sendmailTemplates()
    {
        return array(
            'internalmail_new_message'         => array(
                'title'       => _t('internalmail','Сообщения: новое сообщение'),
                'description' => _t('internalmail','Уведомление, отправляемое <u>пользователю</u> при получении нового сообщения'),
                'vars'        => array(
                    '{name}'    => _t('users','Имя'),
                    '{email}'   => _t('','Email'),
                    '{link}'    => _t('internalmail','Ссылка для прочтения'),
                    '{message}' => _t('internalmail','Текст сообщения'),
                )
            ,
                'impl'        => true,
                'priority'    => 30,
                'enotify'     => Users::ENOTIFY_INTERNALMAIL,
            ),
            'internalmail_new_message_newuser' => array(
                'title'       => _t('internalmail','Сообщения: новое сообщения для неактивированного пользователя'),
                'description' => _t('internalmail','Уведомление, отправляемое <u>неактивированному пользователю</u>'),
                'vars'        => array(
                    '{link_activate}' => _t('internalmail','Ссылка на переписку и активацию'),
                    '{message}'       => _t('internalmail','Текст отправленного сообщения'),
                )
            ,
                'impl'        => true,
                'priority'    => 31,
                'enotify'     => 0, # всегда
            ),
        );
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        $url = $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # сообщения пользователя
            case 'my.messages':
                $url .= '/cabinet/messages' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # переписка
            case 'my.chat':
                $url .= '/cabinet/messages/chat' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # сообщения пользователя с фильтрацией по ID объявления
            case 'item.messages':
                $url .= '/cabinet/messages' . (!empty($opts['item']) ? '?qq=item:' . $opts['item'] : '');
                break;
        }
        return bff::filter('internalmail.url', $url, array('key'=>$key, 'opts'=>$opts, 'dynamic'=>$dynamic, 'base'=>$base));
    }

    /**
     * Получаем список папок
     * @return array
     */
    protected function getFolders()
    {
        $folders = array(
            self::FOLDER_FAVORITE => array(
                'title'       => _t('internalmail', 'Избранные'),
                'notforadmin' => false,
                'icon'        => 'fa fa-star',
                'icon-admin'  => 'icon-star',
                'class'       => 'fav'
            ),
            self::FOLDER_IGNORE   => array(
                'title'       => _t('internalmail', 'Игнорирую'),
                'notforadmin' => true,
                'icon'        => 'fa fa-ban',
                'icon-admin'  => 'icon-ban-circle',
                'class'       => 'ignore'
            ),
        );

        return $folders;
    }

    /**
     * Инициализация компонента работы с вложениями
     * @return InternalMailAttachment
     */
    public function attach()
    {
        static $i;
        if (!isset($i)) {
            # до 5 мегабайт
            $i = new InternalMailAttachment(bff::path('im'), config::sys('internalmail.attachments.maxsize', 5242880, TYPE_UINT));
            $i->setAllowedExtensions(explode(',',config::sys('internalmail.attachments.whitelist','jpg,jpeg,gif,png,bmp,tiff,ico,odt,doc,docx,docm,xls,xlsx,xlsm,ppt,rtf,pdf,djvu,zip,gzip,gz,7z,rar,txt,xml')));
            $i->setCheckFreeDiskSpace(false);
        }

        return $i;
    }

    /**
     * Загрузка приложения к сообщению
     * @param string $inputName имя input-file поля
     * @return string имя загруженного файла
     */
    public function attachUpload($inputName = 'attach')
    {
        if (!static::attachmentsEnabled()) {
            return '';
        }

        return $this->attach()->uploadFILES($inputName);
    }

    /**
     * Включены ли вложения
     * @return bool
     */
    public static function attachmentsEnabled()
    {
        return config::sysAdmin('internalmail.attachments', true, TYPE_BOOL);
    }

    /**
     * Включены ли папки: избранные, игнорирую
     * @return bool
     */
    public static function foldersEnabled()
    {
        return config::sysAdmin('internalmail.folders', true, TYPE_BOOL);
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('im') => 'dir', # вложения
        ));
    }
}