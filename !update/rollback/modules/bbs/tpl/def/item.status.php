<?php
/**
 * Статус объявления: результат добавления / редактирования
 * @var $this BBS
 * @var $state string статус
 * @var $user array данные пользователя
 * @var $new_user boolean новый пользователь
 * @var $item array данные об объявлении
 */

$urlAdd = BBS::url('item.add');

switch($state)
{
    # Добавление + публикация
    case 'new.publicated': {
        if( BBS::premoderation() ) {
        ?>
            <p class="align-center">
                <?= _t('item-form', 'После проверки модератором ваше объявление будет опубликовано') ?>
            </p>
        <?
        } else {
        ?>
            <p class="align-center">
                <?= _t('item-form', 'Теперь вы можете <a [link_view]>просмотреть ваше объявление</a> или <a [link_add]>добавить ещё одно</a>',
                    array(
                        'link_view'=>'href="'.$item['link'].'?from=add"',
                        'link_add'=>'href="'.$urlAdd.'"'
                     )) ?>
            </p>
        <? }
        ?>
            <div class="l-page__useful">
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', '<a [link_add]>Добавить объявление</a> в ту же категорию', array('link_add'=>'href="'.$urlAdd.'?cat='.$item['cat_id'].'"')); ?></span>
                </div>
                <? if (User::id()){ ?>
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Назад к <a [link_mylist]>списку своих объявлений</a>', array('link_mylist'=>'href="'.BBS::url('my.items', array('from'=>'add')).'"')); ?></span>
                </div>
                <? } ?>
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Назад на <a [link_home]>главную страницу</a>', array('link_home'=>'href="'.bff::urlBase().'"')); ?></span>
                </div>
            </div>
        <?
    } break;

    # Добавление от неавторизованного пользователя
    case 'new.notactivated': {
        ?>
        <p>
           <?= _t('item-form', 'Ваше объявление сохранено, но ещё не опубликовано.') ?><br />
           <?= _t('item-form', 'Мы выслали вам письмо на адрес <b>[email]</b> со ссылкой для активации, пожалуйста, проверьте вашу почту.', array('email' => $user['email'])) ?><br />
           <?= _t('item-form', 'Если вы не видите письма, проверьте папку Спам, а также правильность написания вашего адреса.') ?>
        </p>
        <div class="l-page__useful">
            <div class="l-page__useful__item">
                <span><?= _t('item-form', '<a [link_add]>Добавить еще одно</a> объявление', array('link_add'=>'href="'.$urlAdd.'"')); ?></span>
            </div>
            <div class="l-page__useful__item">
                <span><?= _t('item-form', '<a [link_add]>Добавить объявление</a> в ту же категорию', array('link_add'=>'href="'.$urlAdd.'?cat='.$item['cat_id'].'"')); ?></span>
            </div>
            <div class="l-page__useful__item">
                <span><?= _t('item-form', 'Назад на <a [link_home]>главную страницу</a>', array('link_home'=>'href="'.bff::urlBase().'"')); ?></span>
            </div>
        </div>
        <?
    } break;

    # Добавление + активация телефона
    case 'new.notactivated.phone': {
        tpl::includeJS('users.auth', false, 4);
        $phone_change_allowed = !$new_user;
        ?>
        <div class="text-center">
            <p>
               <?= _t('item-form', 'Ваше объявление сохранено, но ещё не опубликовано.') ?>
            </p>
            <p>
               <?= _t('users', 'На номер [phone] отправлен код активации.', array('phone'=>'<strong id="j-u-register-phone-current-number">+'.$user['phone_number'].'</strong>')) ?>
            </p>
            <? if($phone_change_allowed) { ?>
            <p>
               <?= _t('users', 'Не получили код подтверждения? Возможно ваш номер написан с ошибкой.') ?>
            </p>
            <? } ?>
        </div>
        <div id="j-u-register-phone-block-code">
            <div class="l-table u-authorize-form_code">

                <div class="l-table-row">
                    <div class="u-authorize-form u-authorize-form_forgot l-table-cell">
                        <form action="" class="form-inline hidden-phone">
                            <label><?= _t('users', 'Код подтверждения') ?></label>
                            <input type="text" class="j-u-register-phone-code-input" placeholder="<?= _te('users', 'Введите код') ?>" />
                            <button type="submit" class="btn j-u-register-phone-code-validate-btn"><?= _t('users', 'Подтвердить') ?></button>
                        </form>
                        <form action="" class="form-horizontal visible-phone">
                            <div class="control-group">
                                <label class="control-label"><?= _t('users', 'Код подтверждения') ?></label>
                                <div class="controls">
                                    <input class="input-block-level j-u-register-phone-code-input" type="text" placeholder="<?= _te('users', 'Введите код') ?>" />
                                </div>
                            </div>
                            <div class="control-group">
                                <div class="controls">
                                    <button type="submit" class="btn j-u-register-phone-code-validate-btn"><?= _t('users', 'Подтвердить') ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <? if($phone_change_allowed) { ?>
                <div class="u-authorize-form_code_link">
                    <a href="#" class="ajax j-u-register-phone-change-step1-btn"><?= _t('users', 'Изменить номер телефона') ?></a>
                </div>
                <? } ?>
                <div class="u-authorize-form_code_link">
                    <a href="#" class="ajax j-u-register-phone-code-resend-btn"><?= _t('users', 'Выслать новый код подтверждения') ?></a>
                </div>
            </div>
        </div>
        <? if($phone_change_allowed) { ?>
        <div class="hide" id="j-u-register-phone-block-phone">
            <div class="l-table u-authorize-form_code">

                <div class="l-table-row">
                    <div class="u-authorize-form u-authorize-form_forgot l-table-cell">
                        <form action="" class="form-inline hidden-phone">
                            <label><?= _t('users', 'Номер телефона') ?></label>
                            <div class="u-control-phone">
                                <?= Users::i()->registerPhoneInput(array('name'=>'phone', 'id'=>'j-u-register-phone-input', 'value'=>'+'.$user['phone_number'])) ?>
                            </div>
                            <button type="button" class="btn j-u-register-phone-change-step2-btn"><?= _t('users', 'Выслать код') ?></button>
                        </form>
                        <form action="" class="form-horizontal visible-phone">
                            <div class="control-group">
                                <label class="control-label"><?= _t('users', 'Номер телефона') ?></label>
                                <div class="controls">
                                    <div class="u-control-phone mrgb0">
                                        <?= Users::i()->registerPhoneInput(array('name'=>'phone', 'id'=>'j-u-register-phone-input-m', 'value'=>'+'.$user['phone_number'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="control-group">
                                <div class="controls">
                                    <button type="button" class="btn j-u-register-phone-change-step2-btn"><?= _t('users', 'Выслать код') ?></button>
                                    <button type="button" class="btn j-u-register-phone-change-step1-btn"><?= _t('users', 'Отмена') ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <? } ?>
        <script type="text/javascript">
        <? js::start(); ?>
            $(function(){
                jUserAuth.registerPhone(<?= func::php2js(array(
                    'lang' => array(
                        'resend_success' => _t('users', 'Код подтверждения был успешно отправлен повторно'),
                        'change_success' => _t('users', 'Код подтверждения был отправлен на указанный вами номер'),
                    ),
                )) ?>);
            });
        <? js::stop(); ?>
        </script>
        <?
    } break;

    # Отредактировали (без изменения статуса)
    case 'edit.normal': {
        ?>
            <p class="align-center">
                <?= _t('item-form', 'Теперь вы можете <a [link_view]>просмотреть ваше объявление</a> или <a [link_add]>добавить ещё одно</a>',
                    array(
                        'link_view'=>'href="'.$item['link'].'?from=edit"',
                        'link_add'=>'href="'.$urlAdd.'"'
                     )) ?>
            </p>
            <div class="l-page__useful">
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', '<a [link_add]>Добавить объявление</a> в ту же категорию', array('link_add'=>'href="'.$urlAdd.'?cat='.$item['cat_id'].'"')); ?></span>
                </div>
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Назад к <a [link_mylist]>списку своих объявлений</a>', array('link_mylist'=>'href="'.BBS::url('my.items', array('from'=>'edit')).'"')); ?></span>
                </div>
            </div>
        <?
    } break;

    # Опубликовали
    case 'edit.publicated': {
        ?>
            <p class="align-center">
                <?= _t('item-form', 'Теперь вы можете <a [link_view]>просмотреть ваше объявление</a> или <a [link_add]>добавить ещё одно</a>',
                    array(
                        'link_view'=>'href="'.$item['link'].'?from=edit"',
                        'link_add'=>'href="'.$urlAdd.'"'
                     )) ?>
            </p>
            <div class="l-page__useful">
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Назад к <a [link_mylist]>списку своих объявлений</a>', array('link_mylist'=>'href="'.BBS::url('my.items', array('from'=>'edit')).'"')); ?></span>
                </div>
            </div>
        <?
    } break;

    # Сняли с публикации
    case 'edit.publicated.out': {
        ?>
            <p class="align-center">
                <?= _t('item-form', 'Теперь ваше объявление недоступно для просмотра') ?>
            </p>
            <div class="l-page__useful">
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Перейти на просмотр <a [link_mylist]>списка своих объявлений</a>', array('link_mylist'=>'href="'.BBS::url('my.items', array('from'=>'edit')).'"')); ?></span>
                </div>
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Назад на <a [link_home]>главную страницу</a>', array('link_home'=>'href="'.bff::urlBase().'"')); ?></span>
                </div>
            </div>
        <?
    } break;

    # Редактирование заблокированного объявления
    case 'edit.blocked.wait': {
        ?>
            <p class="align-center">
                <?= _t('item-form', 'После повторной проверки модератором объявление будет опубликовано') ?>
            </p>
            <div class="l-page__useful">
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Перейти на просмотр <a [link_mylist]>списка своих объявлений</a>', array('link_mylist'=>'href="'.BBS::url('my.items', array('from'=>'edit')).'"')); ?></span>
                </div>
                <div class="l-page__useful__item">
                    <span><?= _t('item-form', 'Назад на <a [link_home]>главную страницу</a>', array('link_home'=>'href="'.bff::urlBase().'"')); ?></span>
                </div>
            </div>
        <?
    } break;

    # Успешно активировали услугу / пакет услуг
    case 'promote.success': {
        ?>
            <p class="align-center">
                <?= _t('bbs', 'Вы успешно активировали услугу для объявления: <br /><a [link]>[title]</a>', array(
                    'link' => 'href="'.$item['link'].'"',
                    'title' => $item['title'],
                )) ?>

            </p>
        <div class="l-page__useful">
            <? if ( $from == 'my' ) { ?>
            <div class="l-page__useful__item">
                <span><?= _t('bbs', 'Вернуться к <a [my_link]>списку своих объявлений</a>', array('my_link'=>'href="'.BBS::url('my.items').'"')) ?></span>
            </div>
            <? } ?>
            <div class="l-page__useful__item">
                <span><?= _t('item-form', 'Назад на <a [link_home]>главную страницу</a>', array('link_home'=>'href="'.bff::urlBase().'"')); ?></span>
            </div>
        </div>
        <?
    } break;
}