<?php

/**
 * Плагинизация: хуки модуля Shops
 * @version 0.11
 * @modified 25.jun.2018
 */

class ShopsHooks
{
    /**
     * Фильтр формирования ссылок модуля, реализованный в статическом методе @see \Shops::url()
     * @see \Hooks::moduleUrl()
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function url(callable $callback, $priority = NULL)
    {
        return bff::hooks()->moduleUrl('shops', $callback, $priority);
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
        return bff::hooks()->moduleViewTemplate('shops', $template, $callback, $priority);
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
        return bff::hooks()->moduleViewTemplateData('shops', $template, $callback, $priority);
    }

    /**
     * Фильтр списка табов на странице магазин (фронтенд)
     * @see \Shops::view()
     * @param callable $callback {
     *   @param array $list список табов
     *   @param array $data:
     *      string 'tab' @ref ключ текущего активного таба
     *      array 'shop' @ref данные магазина
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopViewTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.view.tabs', $callback, $priority);
    }

    /**
     * Фильтр списка настроек размеров изображений логотипа магазина
     * @see \ShopsLogo::initSettings
     * @param callable $callback {
     *   @param array $list список настроек размеров
     *   return: array список настроек размеров
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopLogoSizes(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.logo.sizes', $callback, $priority);
    }

    /**
     * Фильтр списка доступных причин жалоб на магазин
     * @see \Shops::getShopClaimReasons()
     * @param callable $callback {
     *   @param array $list список причин
     *   return: array список причин
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopClaimReasons(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.claim.reasons', $callback, $priority);
    }

    /**
     * Фильтр списка доступных типов ссылок соц. сетей магазина
     * @see \Shops::socialLinksTypes()
     * @param callable $callback {
     *   @param array $list список типов
     *   return: array список типов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopSocialLinksTypes(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.social.links.types', $callback, $priority);
    }

    /**
     * Хук сабмита настроек магазина (фронтенд)
     * @see \Shops::my_settings()
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID магазина
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopSettingsSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.settings.submit', $callback, $priority);
    }

    /**
     * Хук сабмита формы открытия магазина (фронтенд)
     * @see \Shops::my_open()
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopOpenSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.open.submit', $callback, $priority);
    }

    /**
     * Хук валидации данных магазина (фронтенд + админ. панель)
     * @see \Shops::validateShopData
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID магазина (0 - при создании)
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopValidate(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.shop.validate', $callback, $priority);
    }

    /**
     * Фильтр для дополнительной валидации заголовка магазина (при добавлении / редактировании)
     * @see \Shops::validateShopData
     * @param callable $callback {
     *   @param string $title заголовок магазина
     *   return: string заголовок магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopValidateTitle(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.form.title.validate', $callback, $priority);
    }

    /**
     * Фильтр для дополнительной валидации описания магазина (при добавлении / редактировании)
     * @see \Shops::validateShopData
     * @param callable $callback {
     *   @param string $description описание магазина
     *   return: string описание магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopValidateDescription(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.form.descr.validate', $callback, $priority);
    }

    /**
     * Хук события сабмита заявки на закрепление магазина за пользователем (фронтенд)
     * @see \Shops::request
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные заявки
     *      array 'shop' данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function shopRequestSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.request.submit', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы редактирования платных услуг магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID услуги
     *      array 'data' @ref данные услуги
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcServiceForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.svc-service.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы редактирования платных услуг магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID услуги
     *      array 'data' @ref данные услуги
     *      array 'before' данные услуги до редактирования
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcServiceFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.svc-service.submit', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования настроек тарифа услуги "Абонемент" магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование тарифа
     *      array 'data' @ref данные тарифа
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcAbonementForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.svc-abonement.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования настроек тарифа услуги "Абонемент" магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID тарифа
     *      array 'data' @ref данные тарифа
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcAbonementFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.svc-abonement.submit', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы выбора платной услуги магазина (фронтенд)
     * @param callable $callback {
     *   @param array $svc данные о платной услуге
     *   @param array $extra:
     *      array 'data' @ref данные магазине и др.
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivateItemPromote(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.svc.active.shop.promote', $callback, $priority);
    }

    /**
     * Фильтр списка полей данных магазина формируемых перед активацией услуги
     * @see \Shops::svcActivate()
     * @param callable $callback {
     *   @param array $list список полей
     *   return: array список полей
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivateShopFields(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.svc.activate.shop.fields', $callback, $priority);
    }

    /**
     * Фильтр позволяющий реализовать альтернативный сценарий активации платных услуг магазина
     * @see \Shops::svcActivate()
     * @param callable $callback {
     *   @param integer $svcID ID активируемой услуги
     *   @param array $svcData данные об активируемой услуге
     *   @param integer $shopID ID магазина
     *   @param array $shopData данные магазина
     *   @param array $scvSettings настройки услуги
     *   return: integer|boolean ID услуги или true - альтернативный сценарий активации
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivate(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.svc.activate', $callback, $priority);
    }

    /**
     * Хук расширения события активации платной услуги магазина
     * @see \Shops::svcActivateService()
     * @param callable $callback {
     *   @param integer $svcID ID активируемой услуги
     *   @param array $svcData данные об активируемой услуге
     *   @param integer $shopID ID магазина
     *   @param array $shopData данные магазина
     *   @param array $extra:
     *      array 'settings' @ref настройки активиремой услуги
     *      array 'update' @ref данные для сохранения
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivateCustom(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.svc.activate.custom', $callback, $priority);
    }

    /**
     * Хук расширения метода обслуживания данных магазина с активированными платными услугами
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcCron(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.svc.cron', $callback, $priority);
    }

    /**
     * Фильтр формирования описания счета для платных услуг магазина
     * @param callable $callback {
     *   @param string $description описание счета
     *   @param integer $svcID ID активируемой услуги
     *   @param array $svcData данные об активируемой услуге
     *   @param array $shopData ID данные магазина
     *   @param string $itemLink ссылка на магазин, для которого была активирована услуга
     *   return: string описание счета
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcBillDescriptionCustom(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.svc.description.custom', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование магазина
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID магазина или 0 (при добавлении)
     *      array 'data' @ref данные магазина
     *      array 'user' данные владельца магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы магазина (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      integer 'id' ID магазина
     *      array 'data' @ref данные магазина
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shops.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования магазина
     *      array 'data' @ref данные магазина
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.form.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения содержимого таба "Услуги" формы редактирования магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopFormSvc(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.form.svc', $callback, $priority);
    }

    /**
     * Хук расширения содержимого блока с информацией о статусе магазина (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'is_popup' блок отображается во всплывающем окне
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopFormStatus(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.form.status', $callback, $priority);
    }

    /**
     * Хук расширения всплывающего окна с краткой информацией о магазине (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные магазина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopInfo(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.info', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы редактирования заявки на закрепление магазина за пользователем (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование заявки
     *      array 'data' @ref данные заявки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminShopRequestForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.shop.request.form', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования категории (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование категории
     *      array 'data' @ref данные категории
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.category.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования категории (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID категории или 0 (при добавлении)
     *      array 'data' @ref данные категории
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.category.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы категории (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      boolean 'edit' редактирования категории
     *      array 'data' @ref данные категории
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.category.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы категории (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования категории
     *      array 'data' @ref данные категории
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.category.form.tabs.content', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования настроек (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     *      array 'lang' @ref список мультиязычных полей
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.settings.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы настроек (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      string 'tab' ключ активного таба
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.settings.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы настроек (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('shops.admin.settings.tabs.content', $callback, $priority);
    }
}