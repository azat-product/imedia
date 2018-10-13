<?php
/**
 * Авторизация
 * @var $this Users
 * @var $back string URL страницы возврата (после успешной авторизации)
 * @var $providers array провайдеры авторизации через соц. сети
 */
?>
<div class="l-table">
    <div class="l-table-row">
        <div class="l-table-cell u-authorize-form">
            <form id="j-u-login-form" action="" class="form-horizontal">
                <input type="hidden" name="back" value="<?= HTML::escape($back) ?>" />
                <div class="control-group">
                    <label class="control-label" for="j-u-login-email"><?= _t('users', 'Электронная почта') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="email" name="email" id="j-u-login-email" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="j-u-login-pass"><?= _t('users', 'Пароль') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" name="pass" id="j-u-login-pass" placeholder="<?= _te('users', 'Введите ваш пароль') ?>" maxlength="100" />
                    </div>
                </div>
                <? if(Users::loginRemember()): ?>
                <div class="control-group">
                    <label class="control-label"></label>
                    <div class="controls">
                        <label class="checkbox inline">
                            <input type="checkbox" name="remember"/> <?= _t('users', 'Запомнить меня'); ?>
                        </label>
                    </div>
                </div>
                <? endif ;?>

                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn j-submit"><?= _t('users', 'Войти на сайт') ?></button>
                    </div>
                </div>
            </form>
        </div>
        <div class="l-table-cell u-sc hidden-phone">
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
        <div class="u-authorize-blocks__item_caption pull-left"> <?= _t('users', 'Впервые на нашем сайте?') ?> </div>
        <div class="u-authorize-blocks__item_btn pull-right"> <a href="<?= Users::url('register') ?>" class="btn btn-success"><?= _t('users', 'Зарегистрируйтесь') ?></a> </div>
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
        jUserAuth.login(<?= func::php2js(array(
            'login_social_url' => Users::url('login.social'),
            'login_social_return' => $back,
            'lang' => array(
                'email' => _t('users', 'E-mail адрес указан некорректно'),
                'pass' => _t('users', 'Укажите пароль'),
            ),
        )) ?>);
    });
<? js::stop(); ?>
</script>