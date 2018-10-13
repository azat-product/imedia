<?php
/**
 * Регистрация с подтверждением номера телефона
 * @var $this Users
 */
?>
<div class="text-center">
<p>
    <?= _t('users', 'На номер [phone] отправлен код подтверждения.', array('phone'=>'<strong id="j-u-register-phone-current-number">+'.$phone.'</strong>')) ?>
</p>
<p>
    <?= _t('users', 'Не получили код подтверждения? Возможно ваш номер написан с ошибкой.') ?>
</p>
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
        <div class="u-authorize-form_code_link">
            <a href="#" class="ajax j-u-register-phone-change-step1-btn"><?= _t('users', 'Изменить номер телефона') ?></a>
        </div>
        <div class="u-authorize-form_code_link">
            <a href="#" class="ajax j-u-register-phone-code-resend-btn"><?= _t('users', 'Выслать новый код подтверждения') ?></a>
        </div>
    </div>
</div>

<div class="hide" id="j-u-register-phone-block-phone">
    <div class="l-table u-authorize-form_code">

        <div class="l-table-row">
            <div class="u-authorize-form u-authorize-form_forgot l-table-cell">
                <form action="" class="form-inline hidden-phone">
                    <label><?= _t('users', 'Номер телефона') ?></label>
                    <div class="u-control-phone">
                        <?= $this->registerPhoneInput(array('name'=>'phone', 'id'=>'j-u-register-phone-input')) ?>
                    </div>
                    <button type="button" class="btn j-u-register-phone-change-step2-btn"><?= _t('users', 'Выслать код') ?></button>
                </form>
                <form action="" class="form-horizontal visible-phone">
                    <div class="control-group">
                        <label class="control-label"><?= _t('users', 'Номер телефона') ?></label>
                        <div class="controls">
                            <div class="u-control-phone mrgb0">
                                <?= $this->registerPhoneInput(array('name'=>'phone', 'id'=>'j-u-register-phone-input-m')) ?>
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