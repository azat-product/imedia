<?php

/**
 * Плагинизация: хуки модуля Site
 * @version 0.1
 * @modified 11.jul.2017
 */

class SiteHooks
{
    /**
     * Хук расширения HTML формы настроек сайта: "Настройки сайта / Общие настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref настройки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormGeneral(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.admin.settings.form.general', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы настроек сайта: "Настройки сайта / Общие настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref настройки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.admin.settings.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы настроек сайта: "Настройки сайта / Общие настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов в формате:
     *      array(
     *          'уникальный ключ ланитицей' => array(
     *              't' => 'название таба'
     *          ),
     *          ...
     *      )
     *   @param array $data:
     *      array 'data' @ref настройки
     *      string 'tab' ключ активного таба
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.admin.settings.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы настроек сайта: "Настройки сайта / Общие настройки" (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные настроек для сохранения и дополнения
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminSettingsFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.admin.settings.tabs.content', $callback, $priority);
    }

    /**
     * Фильтр списка доступных провайдеров курсов валют
     * @see SiteCurrencyRate_
     * @param callable $callback {
     *   @param array $list список провайдеров в формате:
     *      array(
     *          'ключ валюты, например: uah' => array(
     *              'method' => 'название метода SiteCurrencyRate_, например: bank_gov_ua'
     *              'title' => 'название провайдера, например: bank.gov.ua'
     *          ),
     *          ...
     *      )
     *   return: array список провайдеров
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function currencyRateAutoProviders(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('currency.rate.auto.providers', $callback, $priority);
    }

    /**
     * Фильтр списка вариантов шаблонов главной страницы
     * @see Site::indexTemplates
     * @param callable $callback {
     *   @param array $list список шаблонов в формате:
     *      array(
     *          'название файла шаблона без расширения, например: index.default' => array(
     *              'title' => 'название шаблона, например: Обычный'
     *              'map' => true/false, // используется ли в шаблоне карта
     *              'regions' => true/false, // используются ли в шаблоне регионы
     *          ),
     *          ...
     *      )
     *   return: array список шаблонов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function indexTemplatesList(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.index.templates', $callback, $priority);
    }

    /**
     * Фильтр расширения списка языков доступных для выбора пользователю
     * @param callable $callback {
     *   @param array $list список языков:
     *   $list['ключ языка'] = [
     *          'title' => 'Название языка',
     *          'priority' => 1, # приоритет отображения в списке, или false - в конец списка
     *      ];
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function languagesList(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.languages.list', $callback, $priority);
    }

    /**
     * Фильтр названия сайта
     * @param callable $callback {
     *   @param string $title название сайта
     *   @param string $position позиция
     *   @param string $language язык
     *   @param string $default название по умолчанию
     *   return string $title итоговое название
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function title(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('site.title', $callback, $priority);
    }
}