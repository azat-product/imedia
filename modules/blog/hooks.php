<?php

/**
 * Плагинизация: хуки модуля Blog
 * @version 0.1
 * @modified 11.jul.2017
 */

class BlogHooks
{
    /**
     * Хук расширения HTML формы добавления/редактирования поста (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование поста
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminPostForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('blog.admin.post.form', $callback, $priority);
    }

    /**
     * Хук сабмита HTML формы добавления/редактирования поста (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID поста или 0 (при добавлении)
     *      array 'data' @ref данные формы
     *      \bff\db\Publicator 'publicator' объект компонента Publicator
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminPostFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('blog.admin.post.submit', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы поста (админ. панель)
     * @param callable $callback {
     *   @param array $tabs список табов
     *   @param array $data:
     *      boolean 'edit' редактирования поста
     *      array 'data' @ref данные формы
     *   return: array список табов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminPostFormTabs(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('blog.admin.post.form.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы поста (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирования поста
     *      array 'data' @ref данные поста
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminPostFormTabsContent(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('blog.admin.post.form.tabs.content', $callback, $priority);
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
        return bff::hooks()->add('blog.admin.category.form', $callback, $priority);
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
        return bff::hooks()->add('blog.admin.category.submit', $callback, $priority);
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
        return bff::hooks()->add('blog.admin.category.form.tabs', $callback, $priority);
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
        return bff::hooks()->add('blog.admin.category.form.tabs.content', $callback, $priority);
    }
}