<?php
/**
 * Восстановление пароля: Шаг 2
 * @var $this Users
 * @var $key string ключ восстановления
 * @var $social integer инициировано ли восстановление на этапе авторизации через соц. сеть
 */
?>
<div class="l-table">
    <div class="l-table-row">
        <div class="u-authorize-form u-authorize-form_forgot l-table-cell">
            <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
            <form action="" id="j-u-forgot-finish-form-<?= bff::DEVICE_DESKTOP ?>" class="form-inline hidden-phone">
                <input type="hidden" name="key" value="<?= HTML::escape($key) ?>" />
                <input type="hidden" name="social" value="<?= $social ?>" />
                <label for=""><?= _t('users', 'Новый пароль') ?></label>
                <input type="password" name="pass" class="j-required" id="j-u-forgot-finish-desktop-pass" placeholder="<?= _te('users', 'Введите пароль') ?>" maxlength="100" />
                <button type="submit" class="btn"><?= _t('users', 'Изменить пароль') ?></button>
            </form>
            <? } ?>
            <? if(DEVICE_PHONE) { ?>
            <form action="" id="j-u-forgot-finish-form-<?= bff::DEVICE_PHONE ?>" class="form-horizontal visible-phone">
                <input type="hidden" name="key" value="<?= HTML::escape($key) ?>" />
                <input type="hidden" name="social" value="<?= $social ?>" />
                <div class="control-group">
                    <label class="control-label" for="j-u-forgot-finish-phone-pass"><?= _t('users', 'Новый пароль') ?></label>
                    <div class="controls">
                        <input class="input-block-level j-required" name="pass" id="j-u-forgot-finish-phone-pass" type="password" placeholder="<?= _te('users', 'Введите пароль') ?>" maxlength="100" />
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn j-submit"><?= _t('users', 'Изменить пароль') ?></button>
                    </div>
                </div>
            </form>
            <? } ?>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jUserAuth.forgotFinish(<?= func::php2js(array(
            'lang' => array(
                'pass' => _t('users', 'Укажите пароль'),
                'success' => _t('users', 'Ваш пароль был успешно изменен.'),
            ),
        )) ?>);
    });
<? js::stop(); ?>
</script>