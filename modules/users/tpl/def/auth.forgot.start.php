<?php
/**
 * Восстановление пароля: Шаг 1
 * @var $this Users
 * @var $social integer инициировано ли восстановление на этапе авторизации через соц. сеть
 */
?>
<div class="l-table">
    <div class="l-table-row">
        <div class="u-authorize-form u-authorize-form_forgot l-table-cell">
            <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
            <form action="" id="j-u-forgot-start-form-<?= bff::DEVICE_DESKTOP ?>" class="form-inline hidden-phone">
                <input type="hidden" name="social" value="<?= $social ?>" />
                <label for=""><?= _t('users', 'Электронная почта') ?></label>
                <input class="j-required" type="email" name="email" id="j-u-forgot-start-desktop-email" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                <button type="submit" class="btn"><?= _t('users', 'Восстановить пароль') ?></button>
            </form>
            <? } ?>
            <? if(DEVICE_PHONE) { ?>
            <form action="" id="j-u-forgot-start-form-<?= bff::DEVICE_PHONE ?>" class="form-horizontal visible-phone">
                <input type="hidden" name="social" value="<?= $social ?>" />
                <div class="control-group">
                    <label class="control-label" for="j-u-forgot-start-phone-email"><?= _t('users', 'Электронная почта') ?></label>
                    <div class="controls">
                        <input class="input-block-level j-required" name="email" id="j-u-forgot-start-phone-email" type="email" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn j-submit"><?= _t('users', 'Восстановить пароль') ?></button>
                    </div>
                </div>
            </form>
            <? } ?>
        </div>
    </div>
</div>
<div class="u-authorize-blocks">
    <div class="u-authorize-blocks__item well">
        <div class="u-authorize-blocks__item_caption pull-left"> <?= _t('users', 'Впервые на нашем сайте?') ?> </div>
        <div class="u-authorize-blocks__item_btn pull-right"> <a href="<?= Users::url('register') ?>" class="btn btn-success"><?= _t('users', 'Зарегистрируйтесь') ?></a> </div>
        <div class="clearfix"></div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jUserAuth.forgotStart(<?= func::php2js(array(
            'lang' => array(
                'email' => _t('users', 'E-mail адрес указан некорректно'),
                'success' => _t('users', 'На ваш электронный ящик были высланы инструкции по смене пароля.'),
            ),
        )) ?>);
    });
<? js::stop(); ?>
</script>