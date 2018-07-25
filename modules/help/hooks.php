<?php

/**
 * Плагинизация: хуки модуля Help
 * @version 0.1
 * @modified 11.jul.2017
 */

class HelpHooks
{
    /**
     * Хук расширения HTML формы добавления/редактирования вопроса (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование вопроса
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminQuestionForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('help.admin.question.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования вопроса (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID вопроса или 0 (при добавлении)
     *      array 'data' @ref данные формы
     *      \bff\db\Publicator 'publicator' объект компонента Publicator
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminQuestionFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('help.admin.question.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы вопроса (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      boolean 'edit' редактирования вопроса
     *      array 'data' @ref данные формы
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminQuestionFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('help.admin.question.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы вопроса (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования вопроса
     *      array 'data' @ref данные формы
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminQuestionFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('help.admin.question.form.tabs.content', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования категории (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование категории
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('help.admin.category.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования категории (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID категории или 0 (при добавлении)
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminCategoryFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('help.admin.category.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы категории (админ. панель)
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
        return bff::hooks()->add('help.admin.category.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы категории (админ. панель)
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
        return bff::hooks()->add('help.admin.category.form.tabs.content', $callback, $priority);
    }
}