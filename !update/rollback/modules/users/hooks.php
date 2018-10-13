<?php

/**
 * Плагинизация: хуки модуля Users
 * @version 0.1
 * @modified 11.jul.2017
 */

class UsersHooks
{
    /**
     * Фильтр формирования ссылок модуля, реализованный в статическом методе @see \Users::url()
     * @see \Hooks::moduleUrl()
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function url(callable $callback, $priority = NULL)
    {
        return bff::hooks()->moduleUrl('users', $callback, $priority);
    }

    /**
     * Фильтр для пост-обработки шаблонов модуля
     * @see \Hooks::moduleViewTemplate()
     * @param string $template название файла шаблона модуля (без расширения)
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function viewTemplate($template, callable $callback, $priority = NULL)
    {
        return bff::hooks()->moduleViewTemplate('users', $template, $callback, $priority);
    }

    /**
     * Хук для предварительной обработки данных шаблонов модуля
     * @see \Hooks::moduleViewTemplateData()
     * @param string $template название файла шаблона модуля (без расширения)
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function viewTemplateData($template, callable $callback, $priority = NULL)
    {
        return bff::hooks()->moduleViewTemplateData('users', $template, $callback, $priority);
    }

    /**
     * Фильтр альтернативной реализации валидации полей пользователя
     * @see \Users::cleanUserData()
     * @param string $dataKey ключ поля
     * @param callable $callback {
     *   @param mixed $value данные требующие обработки
     *   @param array $data:
     *      array 'data' @ref данные пользователя
     *      array 'extraSettings' доп. настройки валидации
     *   return: mixed обработанные данные
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userValidateData($dataKey, callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.clean.data.'.$dataKey, $callback, $priority);
    }

    /**
     * Фильтр списка пунктов меню профиля пользователя в шапке сайта (фронтенд)
     * @see \Users::my_header_menu()
     * @param callable $callback {
     *   @param array $list список пунктов меню в формате:
     *      array(
     *          'уникальный ключ латиницей' => array(
     *              't'   => 'название',
     *              'i'   => 'класс иконки, например: fa fa-shopping-cart',
     *              'url' => 'URL страницы',
     *              'priority' => 'приоритет определяющий порядок, например: 1',
     *          ),
     *          ...
     *          // пример разделителя:
     *          'D',
     *      )
     *   @param array $userData данные пользователя
     *   return: array список пунктов меню
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userHeaderMenu(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.header.user.menu', $callback, $priority);
    }

    /**
     * Фильтр списка табов профиля пользователя (фронтенд)
     * @see \Users::profile()
     * @param callable $callback {
     *   @param array $list список табов в формате:
     *      array(
     *          'уникальный ключ латиницей' => array(
     *              't'   => 'название таба',
     *              'ev'  => array($this, 'название функции плагина'),
     *              'url' => static::urlProfile($data['user']['login'], 'уникальный ключ латиницей для URL'),
     *              'priority' => 'приоритет определяющий порядок, например: 1',
     *          ),
     *          ...
     *      )
     *   @param array $data:
     *      string 'tab' @ref ключ текущего активного таба
     *      array 'user' @ref данные пользователя
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userProfileTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.profile.tabs', $callback, $priority);
    }

    /**
     * Фильтр списка табов кабинета пользователя (фронтенд)
     * @see \Users::my()
     * @param callable $callback {
     *   @param array $list список табов в формате:
     *      array(
     *          'уникальный ключ латиницей' => array(
     *              't'   => 'название таба',
     *              'callback'  => array($this, 'название функции плагина'),
     *              'url' => 'URL',
     *              'priority' => 'приоритет определяющий порядок, например: 1',
     *          ),
     *          ...
     *      )
     *   @param array $data:
     *      string 'tab' @ref ключ текущего активного таба
     *      array 'header' @ref меню в шапке
     *      array 'counters' счетчики
     *      numeric 'balance' баланс пользователя
     *      integer 'userID' ID пользователя
     *      integer 'shopID' ID магазина или 0
     *      integer 'publisher' тип публикации объявлений, системная настройка 'bbs.publisher'
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userCabinetTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.cabinet.tabs', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' форма редактирования
     *      array 'data' @ref данные пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.user.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID пользователя или 0 (при добавлении)
     *      array 'data' @ref данные пользователя
     *      array 'groups' @ref группы пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.user.submit', $callback, $priority);
    }

    /**
     * Хук сабмита данных магазина в форме редактирования пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID магазина
     *      array 'data' @ref данные магазина
     *      array 'user' данные пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserFormSubmitShop(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.shop.submit', $callback, $priority);
    }

    /**
     * Хук для дополнительных табов формы добавления/редактирования пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' форма редактирования
     *      array 'data' @ref данные пользователя
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserFormTabsExtra(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.user.form.tabs.extra', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы добавления/редактирования пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования пользователя
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.user.form.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения содержимого блока с информацией о статусе пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные пользователя
     *      boolean 'popup' блок отображается во всплывающем окне
     *      string 'blockID' ID блока
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserFormStatus(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.user.form.status', $callback, $priority);
    }

    /**
     * Хук расширения HTML краткой формы профиля пользователя (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserProfileForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.profile.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML краткой формы профиля пользователя (админ. панель)
     * @see \Users::profile
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID пользователя
     *      array 'data' @ref данные пользователя для сохранения
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserProfileFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.profile.submit', $callback, $priority);
    }

    /**
     * Хук расширения всплывающего окна с краткой информацией о пользователе (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserInfo(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.user.info', $callback, $priority);
    }

    /**
     * Хук успешной авторизации пользователя (фронтенд / админ. панель)
     * @see \Users::userAuth
     * @param callable $callback {
     *   @param integer $userID ID пользователя
     *   @param array $userData данные пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userLogin(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.user.login', $callback, $priority);
    }

    /**
     * Хук успешной регистрации пользователя (фронтенд)
     * @see \Users::userRegister
     * @param callable $callback {
     *   @param integer $userID ID пользователя
     *   @param array $userData данные пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userRegister(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.user.register', $callback, $priority);
    }

    /**
     * Хук сабмита формы регистрации пользователя (фронтенд)
     * @see \Users::register
     * @param callable $callback {
     *   @param array $data @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userRegisterFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.register.submit', $callback, $priority);
    }

    /**
     * Хук успешной активации пользователя
     * @param callable $callback {
     *   @param integer $userID ID пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userActivate(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.user.activated', $callback, $priority);
    }

    /**
     * Хук некоторых этапов авторизации пользователя в админ. панель
     * @param integer $step шаг:
     *   1 - перед проверкой логина/пароля
     *   2 - после проверкой логина/пароля, блокировки и группы доступа в админ. панель
     * @param callable $callback {
     *   @param array $data:
     *      string 'login' логин пользователя (шаг №1)
     *      string 'password' пароль пользователя (шаг №1)
     *      integer 'id' ID пользователя (шаг №2)
     *      array 'data' @ref данные пользователя (шаг №2)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminUserLogin($step = 1, callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.admin.login.step'.$step, $callback, $priority);
    }

    /**
     * Хук завершения сессии (logout) пользователя (фронтенд / админ. панель)
     * @param callable $callback {
     *   @param integer $id ID пользователя
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userLogout(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.user.logout', $callback, $priority);
    }

    /**
     * Фильтр реализующий возможность подключения сторонних SMS провайдеров
     * @see \UsersSMS_::send
     * @param callable $callback {
     *   @param string $provider ключ SMS провайдера
     *   @param string $phoneNumber номер телефона
     *   @param string $message текст сообщения
     *   return: string|bool ключ SMS провайдера или true
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function smsSend(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.sms.send', $callback, $priority);
    }

    /**
     * Фильтр списка настроек подписки на email-уведомления (фронтенд)
     * @see \Users::getEnotifyTypes()
     * @param callable $callback {
     *   @param array $list список настроек в формате:
     *      array(
     *          'уникальный числовой ключ с побитовым шагом' => array(
     *              'title' => 'название',
     *              'priority' => 'приоритет определяющий порядок, например: 1',
     *          ),
     *          ...
     *      )
     *   @param integer $settings текущие настройки
     *   return: array список настроек
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function userEnotifyTypes(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('users.enotify.types', $callback, $priority);
    }
}