<?php
/**
 * Сообщение: в доступе отказано
 * @var $message string текст сообщения
 * @var $auth boolean требуется авторизация
 */
?>
<div class="alert alert-error">
    <?= (!empty($message) ? $message : '<br />'); ?>
    <? if($auth) { ?><br /><?= _t('', '<a [link_login]>Войдите</a> или <a [link_register]>зарегистрируйтесь</a>',
                    array('link_login'    => 'href="'.Users::url('login').'"',
                          'link_register' => 'href="'.Users::url('register').'"',)) ?><? } ?>
</div>