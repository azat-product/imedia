<?php
/**
 * Регистрация - форма регистрации
 * @var $this Users
 * @var $back string URL страницы возврата (после успешной регистрации)
 * @var $phone_on boolean включена регистрация с вводом номера телефона
 * @var $pass_confirm_on boolean включено подтверждение пароля
 * @var $captcha_on boolean включена капча
 * @var $providers array провайдеры авторизации через соц. сети
 */
?>
<div class="l-table">
    <div class="l-table-row">
        <div class="u-authorize-form l-table-cell">
            <form id="j-u-register-form" action="" class="form-horizontal">
                <input type="hidden" name="back" value="<?= HTML::escape($back) ?>" />
                <? if($phone_on) { ?>
                <div class="control-group">
                    <label class="control-label" for="j-u-register-phone"><?= _t('users', 'Телефон') ?><span class="required-mark">*</span></label>
                    <div class="controls form-control-phone">
                        <?= $this->registerPhoneInput(array('id'=>'j-u-register-phone','name'=>'phone')) ?>
                    </div>
                </div>
                <? } ?>
                <div class="control-group">
                    <label class="control-label" for="j-u-register-email"><?= _t('users', 'Электронная почта') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="email" name="email" class="j-required" id="j-u-register-email" autocomplete="off" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="j-u-register-pass"><?= _t('users', 'Пароль') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" name="pass" class="j-required" id="j-u-register-pass" autocomplete="off" placeholder="<?= _te('users', 'Введите ваш пароль') ?>" maxlength="100" />
                    </div>
                </div>
                <? if($pass_confirm_on) { ?>
                <div class="control-group">
                    <label class="control-label" for="j-u-register-pass2"><?= _t('users', 'Повторите пароль') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" name="pass2" class="j-required" id="j-u-register-pass2" autocomplete="off" placeholder="<?= _te('users', 'Введите пароль ещё раз') ?>" maxlength="100" />
                    </div>
                </div>
                <? } ?>
                <? if($captcha_on) { ?>
                    <?php if (Site::captchaCustom('users-auth-register')) { ?>
                    <div class="control-group">
                        <?php bff::hook('captcha.custom.view', 'users-auth-register', __FILE__, ['wrap'=>'<div class="controls">{form}</div>']); ?>
                    </div>
                    <?php } else { ?>
                    <div class="control-group">
                        <label class="control-label" for="j-u-register-captcha"><?= _t('users', 'Результат с картинки') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="text" name="captcha" id="j-u-register-captcha" autocomplete="off" class="input-small j-required" value="" pattern="[0-9]*" /> <img src="<?= tpl::captchaURL() ?>" class="j-captcha" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&rnd='+Math.random())" />
                        </div>
                    </div>
                    <?php } ?>
                <? } ?>
                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox">
                            <input type="checkbox" name="agreement" id="j-u-register-agreement" autocomplete="off" /> <small><?= _t('users', 'Я соглашаюсь с <a href="[link_agreement]" target="_blank">правилами использования сервиса</a>, а также с передачей и обработкой моих данных.', array('link_agreement'=>Users::url('agreement'))) ?><span class="required-mark">*</span></small>
                        </label>
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn j-submit"><?= _t('users', 'Зарегистрироваться') ?></button>
                    </div>
                </div>
            </form>
        </div>
        <div class="u-sc l-table-cell hidden-phone">
            <? foreach($providers as $v) {

            ?><a href="#" class="btn u-sc_<?= $v['class'] ?> j-u-login-social-btn" data="{provider:'<?= $v['key'] ?>',w:<?= $v['w'] ?>,h:<?= $v['h'] ?>}"><?= $v['title'] ?></a><br /><?

            } ?>
        </div>
    </div>
</div>
<div class="u-sc visible-phone">
    <div class="l-spacer"></div>
    <span><?= _t('users', 'Войдите через:') ?></span>
    <? foreach($providers as $v) {

            ?>
                <a href="#" class="btn u-sc_<?= $v['class'] ?> j-u-login-social-btn" data="{provider:'<?= $v['key'] ?>',w:<?= $v['w'] ?>,h:<?= $v['h'] ?>}"><?= $v['title'] ?></a>
            <?

    } ?>
    <div class="l-spacer"></div>
</div>
<div class="u-authorize-blocks">
    <div class="u-authorize-blocks__item well">
        <div class="u-authorize-blocks__item_caption pull-left"> <?= _t('users', 'Вы уже зарегистрированы?') ?> </div>
        <div class="u-authorize-blocks__item_btn pull-right"> <a href="<?= Users::url('login') ?>" class="btn btn-success"><?= _t('users', 'Войдите на сайт') ?></a> </div>
        <div class="clearfix"></div>
    </div>
    <div class="u-authorize-blocks__item well">
        <div class="u-authorize-blocks__item_caption pull-left"> <?= _t('users', 'Вы забыли свой пароль?') ?> </div>
        <div class="u-authorize-blocks__item_btn pull-right"> <a href="<?= Users::url('forgot') ?>" class="btn"><?= _t('users', 'Восстановить пароль') ?></a> </div>
        <div class="clearfix"></div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jUserAuth.register(<?= func::php2js(array(
            'phone' => $phone_on,
            'captcha' => !empty($captcha_on) && ! Site::captchaCustom('users-auth-register'),
            'pass_confirm' => !empty($pass_confirm_on),
            'login_social_url' => Users::url('login.social'),
            'login_social_return' => $back,
            'lang' => array(
                'email' => _t('users', 'E-mail адрес указан некорректно'),
                'pass' => _t('users', 'Укажите пароль'),
                'pass2' => _t('users', 'Пароли должны совпадать'),
                'captcha' => _t('users', 'Введите результат с картинки'),
                'agreement' => _t('users', 'Пожалуйста подтвердите, что Вы согласны с пользовательским соглашением'),
            ),
        )) ?>);
    });
<? js::stop(); ?>
</script>