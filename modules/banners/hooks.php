<?php

class BannersHooks
{
    /**
     * Хук расширения HTML формы добавления/редактирования баннера (админ. панель)
     * @see /modules/banners/tpl/def/admin.form.php
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование баннера
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminBannerForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('banners.admin.banner.form', $callback, $priority);
    }

    /**
     * Фильтр расширения списка типа баннеров в HTML форме добавления/редактирования баннера (админ. панель)
     * @see /modules/banners/tpl/def/admin.form.php
     * @param callable $callback {
     *   @param array $list список типов
     *   @param array $data:
     *      boolean 'edit' редактирование баннера
     *   return: array список типов
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminBannerFormTypes(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('banners.admin.banner.form.types', $callback, $priority);
    }

    /**
     * Хук сабмита формы добавления/редактирования баннера (админ. панель)
     * @see \Banners::add(), \Banners::edit()
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID баннера или 0 (при добавлении)
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminBannerFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('banners.admin.banner.submit', $callback, $priority);
    }

    /**
     * Хук расширения HTML формы добавления/редактирования позиций баннеров (админ. панель)
     * @see /modules/banners/tpl/def/admin.positions.form.php
     * @param callable $callback {
     *   @param array $data:
     *      boolean 'edit' редактирование позиции
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminPositionForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('banners.admin.position.form', $callback, $priority);
    }

    /**
     * Хук сабмита формы добавления/редактирования позиций баннеров (админ. панель)
     * @see \Banners::positions()
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID позиции или 0 (при добавлении)
     *      array 'data' @ref данные формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminPositionFormSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('banners.admin.position.submit', $callback, $priority);
    }
}