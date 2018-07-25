<?php

/**
 * Плагинизация: хуки модуля Sendmail
 * @version 0.1
 * @modified 11.jul.2017
 */

class SendmailHooks
{
    /**
     * Хук расширения HTML формы создания массовой рассылки писем (админ. панель)
     * @see /modules/sendmail/tpl/def/admin.massend.php
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function adminMassendForm(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('sendmail.admin.massend.form', $callback, $priority);
    }
}