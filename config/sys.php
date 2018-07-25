<?php
/**
 * Системные настройки
 */

$config = array(
    'site.host'   => 'imedia.kz',
    'site.static' => '//imedia.kz',
    'site.title'  => 'Imedia.kz', // название сайта, для подобных случаев: "Я уже зарегистрирован на {Imedia.kz}"
    'https.only'  => true,
    /**
     * Доступ к базе данных
     */
    'db.type' => 'mysql', // варианты: pgsql, mysql
    'db.host' => 'localhost', // варианты: localhost, ...
    'db.port' => '3306', // варианты: pgsql - 5432, mysql - 3306
    'db.name' => 'imedia_kz',
    'db.user' => 'imedia_kz',
    'db.pass' => 'b_B~p2a2ksQxus',
    'db.charset' => 'UTF8',
    'db.prefix' => 'bff_',
    /**
     * Mail
     */
    'mail.support'  => 'support@imedia.kz',
    'mail.noreply'  => 'noreply@imedia.kz',
    'mail.admin'    => 'admin@imedia.kz',
    'mail.fromname' => 'Imedia.kz',
    'mail.method'   => 'mail', // варианты: mail, sendmail, smtp
    'mail.smtp' => array(
        'host'=>'localhost',
        'port'=>25,
        'user'=>'',
        'pass'=>'',
        'secure'=>'', // варианты: '', 'tls', 'ssl'
    ),
    /**
     * Локализация
     * Подробности добавления дополнительных локализаций описаны в файле /install/faq.txt
     */
     'locale.available' => array( // список языков (используемых на сайте)
        // ключ языка => название языка
        'ru' => 'Русский',
        //'uk' => 'Українська',
        //'en' => 'English',
     ),
     'locale.default' => 'ru', // язык по-умолчанию
     //'locale.default.admin' => 'ru', // язык по-умолчанию в админ. панели
     'locale.hidden' => array( // языки скрытые от пользователей сайта
        //'en',
     ),
    /**
     * Настройки услуг и систем оплаты (bills)
     * Полный список доступных настроек указан в BillsModuleBase::init методе
     * Также настройка доступных способов оплаты для пользователя настраивается в методе BillsBase::getPaySystems
     * Подробности добавления дополнительных систем оплаты описаны в файле /install/faq.txt
     */
    'services.enabled' => true, // платные услуги (true - включены, false - выключены)
    'bills.robox.test' => true,
    'bills.robox.login' => 'imedia.kz', // Идентификатор магазина
    'bills.robox.pass1' => '', // Пароль #1
    'bills.robox.pass2' => '', // Пароль #2
    'bills.paypal.button_id' => '', // ID кнопки оплаты PayPal, например 'UMTZ9XW8PDPE8'
    'bills.paypal.currency' => 'USD', // Валюта оплаты PayPal
    'bills.liqpay.public_key' => '', // Публичный ключ, например 'i539429889'
    'bills.liqpay.private_key' => '', // Приватный ключ, например 'W2UHWJPWpOWrqUJH9TEJK31zrTxj5jzCkwuALfw9'
    'bills.liqpay.currency' => 'UAH', // Валюта платежа, возможные значения: 'UAH', 'RUB', 'USD', 'EUR'
    'bills.yandex.money.purse'  => '', // Номер кошелька, например '411015109864170'
    'bills.yandex.money.secret' => '', // Секрет, например 'Yb4tKMTfj7Qe35qPEz5E4IoH'
    /**
     * Sphinx (если используется)
     */
    'sphinx.enabled' => true,
    'sphinx.host'    => '127.0.0.1',
    'sphinx.port'    => 9306,
    'sphinx.path'    => '/var/lib/sphinx/',
    'sphinx.prefix'  => '',
    'sphinx.version'  => '2.2.11',
    'bbs.search.sphinx' => true, // Sphinx для основного поиска, требуется дополнительная настройка сервера, false - выключен
    /**
     * Пользователи
     */
    # Настройки SMS:
    'users.sms.provider'      => 'sms_ru', // доступные sms провайдеры: 'sms_ru'
    # -- провайдер sms.ru:
    'users.sms.sms_ru.api_id' => '', // Уникальный ключ (api_id), например: 4ac0c9c0-25xx-77f4-ed29-1519e8719180
    'users.sms.sms_ru.from'   => '', // Имя отправителя: http://sms.ru/?panel=mass&subpanel=senders
    'users.sms.sms_ru.test'   => false, // Тестовая отправка: (варианты: true|false)
    # -- провайдер atompark.com:
    'users.sms.atompark_com.username' => '', // логин пользователя в системе SMS Sender
    'users.sms.atompark_com.password' => '', // пароль пользователя в системе SMS Sender
    'users.sms.atompark_com.sender'   => 'SMS', // отправитель смс, 14 цифровых символов или 11 цифробуквенных (английские буквы и цифры)
    /**
     * Debug (для разработчика)
     */
    'php.errors.reporting' => -1, // all
    'php.errors.display'   => 0, // отображать ошибки (варианты: 
    'debug' => true, // варианты:true|false - включить debug-режим
    /**
     * Дополнительные настройки:
     * ! Настоятельно не рекомендуется изменять после запуска проекта
     */
    'date.timezone' => 'Asia/Almaty', // часовой пояс
    'cookie.prefix' => 'bff_', // cookie префикс
    'config.sys.admin' => true, // Возможность редактирования большей части системных настроек через админ. панель в "режиме разработчика"
    'site.static.minify' => true, // Минимизация файлов статики: js, css
    /**
     * Доступный тип пользователя, публикующего объявление, варианты:
     * 1) 'user' - только пользователь (добавление объявлений доступно сразу, объявления размещаются только "от частного лица"), модуль магазинов(shops) при этом может отсутствовать.
     * 2) 'shop' - только магазин (добавление объявлений доступно после открытия магазина, только "от магазина")

     * 3) 'user-or-shop' - пользователь или магазин (добавление объявлений доступно сразу только "от частного лица", после открытия магазина - объявления размещаются "от частного лица" или "от магазина")
     * 4) 'user-to-shop' - пользователь и магазин (добавление объявлений доступно сразу только "от частного лица", после открытия магазина - объявления размещаются только "от магазина")
     * ! Настоятельно не рекомендуется изменять после запуска проекта
     */
    'bbs.publisher' => 'user-or-shop',
    # SEO
    'seo.landing.pages.enabled' => true, // Задействовать посадочные страницы (варианты: true|false)
    'seo.landing.pages.fields'  => array(
        'titleh1' => array(
            't'=>'Заголовок H1',
            'type'=>'text',
        ),
        'seotext' => array(
            't'=>'SEO текст',
            'type'=>'wy',
        ),
    ),
    'seo.redirects' => true, // Задействовать редиректы (варианты: true|false)
    'device.desktop.responsive' => false, // Responsive для desktop версии сайта (false - выключен)
    # Хуки
    'hooks' => array(
  'bbs.dp.settings' => array(
            'datafield_int_last'   => 30,
            'datafield_text_first' => 31,
            'datafield_text_last'  => 45,
        ),

        # Контакты (поля ввода)
        # Выключая уже используемые поля вы скрываете/удаляете контактные данные указанные пользователями ранее
        'users.contacts.fields' => function($list) {
            return config::merge($list, array(
                'skype'    => ['enabled' => 1],
                'icq'      => ['enabled' => 1],
                'whatsapp' => ['enabled' => 1,
                'regexp' => '/[+]/',
                'view' => '<a href="https://api.whatsapp.com/send?phone={value}&text=Здравствуйте! Мы нашли Вас на imedia.kz">{value}</a>'],
                'viber'    => ['enabled' => 0],
                'telegram' => ['enabled' => 0],
                'example'  => [ # Ключ должен быть уникальным и содержать символы a-z
                    'title'    => _te('', 'Imedia.kz title'),
                    'icon'     => 'fa fa-comment', # http://fontawesome.io/icons/
                    'priority' => 1,
                    'enabled'  => false, # включен - true, выключен - false
                ],
            ));
        },
        # Системы оплаты доступные пользователю:
        # 'enabled' => true, # включено
        # 'enabled' => false, # выключено
        # currency_id - ID валюты в разделе "Настройки сайта / Валюты"
        'bills.pay.systems.user' => function($list, $extra) {
            $list = config::merge($list, array(
                'robox' => array( # Robokassa
                    'enabled' => true,
                    'title'   => _t('bills', 'Robokassa'),
                ),
                'wm' => array( # Webmoney WMZ
                    'enabled' => true,
                    'title'   => _t('bills', 'Webmoney'),
                ),
                'wmr' => array( # Webmoney WMR
                    'enabled' => false,
                    'title'   => _t('bills', 'Webmoney WMR'),
                    'logo_desktop' => $extra['logoUrl'] . 'wm.png',
                    'logo_phone'   => $extra['logoUrl'] . 'wm.png',
                    'way'     => 'wmr',
                    'id'      => Bills::PS_WM,
                    'currency_id' => 2, # рубли
                ),
                'wmu' => array( # Webmoney WMU
                    'enabled' => false,
                    'title'   => _t('bills', 'Webmoney WMU'),
                    'logo_desktop' => $extra['logoUrl'] . 'wm.png',
                    'logo_phone'   => $extra['logoUrl'] . 'wm.png',
                    'way'     => 'wmu',
                    'id'      => Bills::PS_WM,
                    'currency_id' => 1, # гривны
                ),
                'terminal' => array( # W1
                    'enabled' => true,
                    'title'   => _t('bills', 'Терминал'),
                ),
                'paypal' => array( # PayPal
                    'enabled' => false,
                    'title'   => _t('bills', 'Paypal'),
                ),
                'liqpay' => array( # LiqPay
                    'enabled' => false,
                    'title'   => _t('bills', 'Liqpay'),
                ),
                'yandex' => array( # Yandex.Деньги
                    'enabled' => false,
                    'title'   => _t('bills', 'С кошелька'),
                ),
                'yandexAC' => array( # Yandex.Деньги
                    'enabled' => false,
                    'title'   => _t('bills', 'Банковская карта'),
                ),
            ));
            return $list;
        },
        # Блок премиум/последних на главной:
        'bbs.index.last.blocks' => array(
            //'premium', 'last',
        ),
    ),
);

if (file_exists(PATH_BASE.'config'.DIRECTORY_SEPARATOR.'sys-local.php')) {
    $local = include 'sys-local.php';
    return array_merge($config, $local);
}


return $config;
