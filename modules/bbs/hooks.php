<?php

/**
 * Плагинизация: хуки модуля BBS
 * @version 0.11
 * @modified 9.mar.2018
 */

class BbsHooks
{
    /**
     * Фильтр формирования ссылок модуля
     * @see \BBS::url(), \Hooks::moduleUrl()
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function url(callable $callback, $priority = NULL)
    {
        return bff::hooks()->moduleUrl('bbs', $callback, $priority);
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
        return bff::hooks()->moduleViewTemplate('bbs', $template, $callback, $priority);
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
        return bff::hooks()->moduleViewTemplateData('bbs', $template, $callback, $priority);
    }

    /**
     * Фильтр настроек динамических свойств
     * @see \BBS::dp()
     * @param callable $callback {
     *   @param array $settings настройки
     *   return: array настройки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function dynpropsSettings(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.dp.settings', $callback, $priority);
    }

    /**
     * Фильтр дополнительных настроек динамических свойств
     * @see \BBS::dp()
     * @param callable $callback {
     *   @param array $settings настройки
     *   return: array настройки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function dynpropsSettingsExtra(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.dp.settings.extra', $callback, $priority);
    }

    /**
     * Фильтр списка причин блокировки объявления
     * @see \BBS::blockedReasons()
     * @param callable $callback {
     *   @param array $list список причин
     *   return: array список причин
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemBlockedReasons(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.blocked.reasons', $callback, $priority);
    }

    /**
     * Фильтр списка доступных причин жалоб на объявления
     * @see \BBS::getItemClaimReasons()
     * @param callable $callback {
     *   @param array $list список причин
     *   return: array список причин
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemClaimReasons(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.claim.reasons', $callback, $priority);
    }

    /**
     * Фильтр списка настроек размеров изображений объявлений
     * @see BBSItemImages_::initSettings
     * @param callable $callback {
     *   @param array $list список настроек размеров
     *   return: array список настроек размеров
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemImagesSizes(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.images.sizes', $callback, $priority);
    }

    /**
     * Фильтр позволяющий реализовать подключение альтернативного провайдера для перевода заголовка и описания объявлений
     * @see \BBSTranslate::translate()
     * @param callable $callback {
     *   @param array $data данные для перевода: заголовок, описание
     *   @param string $lang текущий язык
     *   @param array $languages данные о языках проекта
     *   @param string $provider ключ провайдера указанный в системной настройке 'bbs.translate'
     *   return: array данные о переводах или false
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemTranslateProvider(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.translate', $callback, $priority);
    }

    /**
     * Фильтр списка доступных провайдеров перевода заголовка и описания объявлений
     * Отображается в админ. панели в разделе "Настройки сайта / Системные настройки / Объявления"
     * @param callable $callback {
     *   @param array $list список доступных провайдеров в формате: array(
     *     'уникальный ключ' => array('title'=>'Название провайдера'),
     *     ...
     *   )
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemTranslateProvidersList(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.translate.providers.list', $callback, $priority);
    }

    /**
     * Фильтр позволяющий реализовать подключение альтернативного провайдера video-ссылок прикрепляемых к объявлению
     * @see \BBSItemVideo::parse()
     * @param callable $callback {
     *   @param string $url URL-ссылки на видео, указанной в форме объявления
     *   return: array данные о видео или false
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemVideoProvider(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.video.parse', $callback, $priority);
    }

    /**
     * Хук добавления объявления (фронтенд)
     * @see \BBS::add()
     * @param integer $step шаг:
     *      1 - после валидации данных
     *      2 - после проверки аккаунта пользователя, лимитов, спам фильтра и др.
     *      3 - после создания объявления в БД
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID объявления (на этапе шага №3)
     *      array 'data' @ref данные о добавляемом объявлении
     *      array 'user' данные о пользователе (авторе объявления)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemAdd($step = 1, callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.item.add.step'.$step, $callback, $priority);
    }

    /**
     * Хук редактирования объявления (фронтенд)
     * @param integer $step шаг: 1 или 2
     *      1 - сразу после валидации данных
     *      2 - после спам фильтром и проверки лимитов
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID объявления
     *      array 'data' @ref данные об объявлении
     *      array 'item' данные о объявлении (до редактирования)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemEdit($step = 1, callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.item.edit.step'.$step, $callback, $priority);
    }

    /**
     * Хук удаления объявления
     * @param callable $callback {
     *   @param array $data данные о объявлении
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemDelete(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.item.delete', $callback, $priority);
    }

    /**
     * Хук валидации данных объявления (фронтенд + админ. панель)
     * @param integer $step шаг: 1 или 2
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID объявления (0 - при создании)
     *      array 'data' @ref данные объявления
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemValidate($step = 1, callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.item.validate.step'.$step, $callback, $priority);
    }

    /**
     * Фильтр для дополнительной валидации заголовка объявления (при добавлении / редактировании)
     * @param callable $callback {
     *   @param string $title заголовок объявления
     *   return: string заголовок объявления
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemValidateTitle(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.form.title.validate', $callback, $priority);
    }

    /**
     * Фильтр для дополнительной валидации описания объявления (при добавлении / редактировании)
     * @param callable $callback {
     *   @param string $description описание объявления
     *   return: string описание объявления
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemValidateDescription(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.form.descr.validate', $callback, $priority);
    }

    /**
     * Фильтр для дополнительной валидации текста комментария объявления
     * @param callable $callback {
     *   @param string $comment текст комментария
     *   return: string текст комментария
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemValidateCommentMessage(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.comments.message.validate', $callback, $priority);
    }

    /**
     * Хук успешной активации нового объявления
     * @param callable $callback {
     *   @param integer $itemID ID объявления
     *   @param array $itemData данные об объявлении
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemStatusNewActivated(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.item.status.new.activated', $callback, $priority);
    }

    /**
     * Хук успешной создания нового объявления
     * @param callable $callback {
     *   @param integer $itemID ID объявления
     *   @param array $itemData данные об объявлении
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemStatusNewCreated(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.item.status.new.created', $callback, $priority);
    }

    /**
     * Фильтр списка возможных дней для оповещения о завершении публикации объявления
     * @param callable $callback {
     *   @param array $list список доступных дней
     *   return: array список доступных дней
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function itemUnpublicateDaysEnotify(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.unpublicate.days.enotify', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование объявления
     *      array 'data' @ref данные формы
     *      callable 'isModerated' функция формирования блока промодерированных ранее данных (при редактировании)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID объявления или 0 (при добавлении)
     *      array 'data' @ref данные формы
     *      array 'before' данные до редактирования (при редактировании)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      boolean 'edit' редактирования объявления
     *      array 'data' @ref данные формы
     *      string 'tab' @ref текущий активный таб
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования объявления
     *      array 'data' @ref данные формы
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.form.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения содержимого таба "Услуги" формы редактирования объявления (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemFormSvc(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.form.svc', $callback, $priority);
    }

    /**
     * Хук расширения содержимого блока "Категория" формы редактирования объявления (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemFormCategory(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.form.category', $callback, $priority);
    }

    /**
     * Хук расширения содержимого блока "Статус" формы редактирования объявления (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'is_popup' всплывающее окно информации об объявлении (true), форма (false)
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemFormStatus(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.form.status', $callback, $priority);
    }

    /**
     * Хук расширения всплывающего окна с краткой информацией об объявлении (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные объявления
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminItemInfo(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.item.info', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования категории объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование категории
     *      array 'data' @ref данные формы
     *      callable 'copyRow' функция формирования столбца выборочного копирования данных
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования категории объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID категории или 0 (при добавлении)
     *      array 'data' @ref данные формы
     *      array 'before' данные до редактирования (при редактировании)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы категории объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      boolean 'edit' редактирования категории
     *      array 'data' @ref данные формы
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы категории объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования категории
     *      array 'data' @ref данные формы
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.form.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы пакетных действий категорий объявлений (админ. панель)
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryPacketActions(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.packetActions.form', $callback, $priority);
    }

    /**
     * Хук сабмита формы пакетных действий категорий объявлений - шаг №1 (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'actions' @ref список действий
     *      array 'catsFields' @ref поля категорий
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryPacketActionsSubmitStep1(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.packetActions.step1', $callback, $priority);
    }

    /**
     * Хук сабмита формы пакетных действий категорий объявлений - шаг №2 (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID категории
     *      array 'data' обновляемые данные категории
     *      array 'actions' @ref список действий
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryPacketActionsSubmitStep2(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.category.packetActions.step2', $callback, $priority);
    }

    /**
     * Хук сабмита формы настроек "Объявления / Настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     *      array 'lang' @ref языковые поля формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.settings.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы настроек "Объявления / Настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      string 'tab' @ref ключ текущего активного таба
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.settings.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы настроек "Объявления / Настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.settings.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы импорта объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.form.import', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы импорта объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы импорта объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы импорта объявлений - таб "Экспорт" (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportFormExport(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.form.export', $callback, $priority);
    }

    /**
     * Хук сабмита формы импорта объявлений (админ. панель)
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.submit', $callback, $priority);
    }

    /**
     * Хук сабмита формы импорта объявлений - загрузка файла (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportSubmitFile(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.file.submit', $callback, $priority);
    }

    /**
     * Хук сабмита формы импорта объявлений - загрузка файла (фронтенд)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function importSubmitFile(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.import.file.submit', $callback, $priority);
    }

    /**
     * Хук сабмита формы импорта объявлений - URL источник (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminImportSubmitUrl(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.import.url.submit', $callback, $priority);
    }

    /**
     * Хук сабмита формы импорта объявлений - URL источник (фронтенд)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function importSubmitUrl(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.import.url.submit', $callback, $priority);
    }

    /**
     * Фильтр списка доступных периодов обработки файла импорта по URL
     * @see BBSItemsImport::importPeriodOptions()
     * @param callable $callback {
     *   @param array $list список периодов
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function importPeriodList(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.items.import.period.list', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы редактирования платных услуг объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID услуги
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcServiceForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.svc-service.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы редактирования платных услуг объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID услуги
     *      array 'data' @ref данные формы
     *      array 'before' данные формы до редактирования
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcServiceFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.svc-service.submit', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления / редактирования платных пакетов услуг объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID пакета услуг
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcPackForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.svc-pack.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы редактирования платных пакетов услуг объявлений (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID пакета услуг (0 - при добавлении)
     *      array 'data' @ref данные формы
     *      array 'before' данные пакета услуг до редактирования (при редактировании)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSvcPackFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.admin.svc-pack.submit', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы выбора платной услуги / пакета услуг объявления (фронтенд)
     * @param callable $callback {
     *   @param array $svc данные о платной услуге / пакете услуг
     *   @param array $extra:
     *      array 'item' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivateItemPromote(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.active.item.promote', $callback, $priority);
    }

    /**
     * Фильтр списка полей данных объявления формируемых перед активацией услуги / пакета услуг
     * @see BBS::svcActivate()
     * @param callable $callback {
     *   @param array $list список полей
     *   return: array список полей
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivateItemFields(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.activate.item.fields', $callback, $priority);
    }

    /**
     * Фильтр позволяющий реализовать альтернативный сценарий активации платных услуг / пакетов услуг объявления
     * @see BBS::svcActivate()
     * @param callable $callback {
     *   @param integer $svcID ID активируемой услуги
     *   @param array $svcData данные об активируемой услуге
     *   @param integer $itemID ID объявления
     *   @param array $itemData данные объявления
     *   @param array $scvSettings настройки услуги
     *   return: integer|boolean ID услуги или true - альтернативный сценарий активации
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivate(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.activate', $callback, $priority);
    }

    /**
     * Хук расширения события активации платной услуги объявления
     * @see BBS::svcActivateService()
     * @param callable $callback {
     *   @param integer $svcID ID активируемой услуги
     *   @param array $svcData данные об активируемой услуге
     *   @param integer $itemID ID объявления
     *   @param array $itemData данные объявления
     *   @param array $extra:
     *      boolean 'fromPack' @ref активация выполняется в рамках пакета услуг
     *      array 'settings' @ref настройки активиремой услуги
     *      array 'update' @ref данные для сохранения
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcActivateCustom(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.activate.custom', $callback, $priority);
    }

    /**
     * Фильтр списка доступных вариантов периодичности услуги автоподнятия
     * @param callable $callback {
     *   @param array $list список вариантов
     *   return: array список вариантов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcUpAutoPeriods(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.upauto.periods', $callback, $priority);
    }

    /**
     * Хук расширения метода обслуживания данных объявлений с активированными платными услугами
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcCron(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.cron', $callback, $priority);
    }

    /**
     * Фильтр формирования описания счета для платных услуг
     * @param callable $callback {
     *   @param string $description описание счета
     *   @param integer $svcID ID активируемой услуги
     *   @param array $svcData данные об активируемой услуге
     *   @param array $itemData ID данные об объявлении
     *   @param string $itemLink ссылка на объявление, для которого была активирована услуга
     *   return: string описание счета
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function svcBillDescriptionCustom(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('bbs.svc.description.custom', $callback, $priority);
    }
}