<?php

/**
 * Плагинизация: хуки модуля Contacts
 * @version 0.1
 * @modified 11.jul.2017
 */

class ContactsHooks
{
    /**
     * Хук сабмита HTML формы контактов
     * @see Contacts::write
     * @param callable $callback {
     *   @param array $data:
     *      array 'data' @ref данные указанные в форме (после их валидации)
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function formSubmit(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('contacts.form.submit', $callback, $priority);
    }

    /**
     * Хук расширения попапа просмотра сообщения (админ. панель)
     * @param callable $callback {
     *   @param array $data:
     *      integer 'id' ID вопроса
     *      array 'data' @ref данные вопроса
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminView(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('contacts.admin.view', $callback, $priority);
    }

    /**
     * Фильтр списка тем доступных в форме контактов
     * @see Contacts::getContactTypes
     * @param callable $callback {
     *   @param array $list список тем в формате:
     *      array(
     *          'уникальный ID' => array(
     *              'title' => 'название темы',
     *              'priority' => 'приоритет в списке',
     *          ),
     *          ...
     *      )
     *   return: array список тем
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function typesList(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('contacts.types.list', $callback, $priority);
    }
}