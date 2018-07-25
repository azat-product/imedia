<?php

/**
 * Плагинизация: хуки модуля InternalMail
 * @version 0.1
 * @modified 11.jul.2017
 */

class InternalmailHooks
{
    /**
     * Фильтр для дополнительной валидации текста сообщения
     * @param callable $callback {
     *   @param string $comment текст сообщения
     *   return: string текст сообщения
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function messageValidate(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('internalmail.message.validate', $callback, $priority);
    }
}