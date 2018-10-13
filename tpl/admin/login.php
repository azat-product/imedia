<?php
/**
 * Страница авторизации в панели управления
 * @var $errors array список ошибок
 */
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= Site::titleHeader('admin.title.login') ?> | <?= _t('', 'Панель управления') ?></title>
<?= View::template('css'); ?>
<?= View::template('js'); ?>
<?php bff::hook('admin.head'); ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="login-logo">
                <img src="<?= Site::logoURL('admin.header.login', Site::LOGO_SIZE_SMALL) ?>" alt="" />
                <span><?= Site::titleHeader('admin.header.login', true, '', _t('', 'Панель администратора')); ?></span>
            </div>
            <!--Start LOGIN block-->
            <div class="login-box">
                <? if( ! empty($errors) ) { ?>
                    <div class="alert alert-error">
                        <button data-dismiss="alert" class="close" type="button">×</button>
                        <? foreach($errors as $v) { echo $v, '<br />'; } ?>
                    </div>
                <? } ?>
                <div class="title"><?= _t('', 'Панель управления') ?></div>
                <div class="icons">
                    <a href="<?= Site::urlBase() ?>"><i class="icon-home"></i></a>
                </div>
                <div class="clearfix"></div>
                <form method="post" action="" class="form-horizontal" id="j-users-admin-login-form">
                    <input type="hidden" name="s" value="users" />
                    <input type="hidden" name="ev" value="login" />
                    <input type="hidden" name="ref" value="<?= HTML::escape(Request::referer()) ?>" class="j-ref" />
                    <fieldset>
                        <div title="<?= _te('', 'логин') ?>" class="input-prepend left" style="margin-left: 20px;">
                            <span class="add-on"><i class="icon-user"></i></span>
                            <input type="text" placeholder="<?= _te('', 'логин') ?>" name="login" id="login" tabindex="1" class="input-large span10 j-login" />
                        </div>
                        <div title="<?= _te('', 'пароль') ?>" class="input-prepend right" style="margin-right: 16px;">
                            <span class="add-on"><i class="icon-lock"></i></span>
                            <input type="password" placeholder="<?= _te('', 'пароль') ?>" name="password" tabindex="2" class="input-large span10 j-password" />
                        </div>
                        <div class="clearfix"></div>
                        <?php bff::hook('users.admin.login.form', array('errors'=>$errors)); ?>
                        <div class="clearfix"></div>
                        <div class="progress left" style="display:none;" id="progress-login"></div>
                        <div class="btn-group button-login right">
                            <button class="btn btn-round btn-small j-submit" type="submit" onclick="document.getElementById('progress-login').style.display='inline-block';" tabindex="3"><img src="<?= bff::url('/img/admin/login.png') ?>" alt="" />&nbsp;&nbsp;<?= _te('', 'Вход') ?></button>
                        </div>
                        <div class="clearfix"></div>
                    </fieldset>
                </form>
                <script type="text/javascript">
                    document.getElementById('login').focus();
                </script>
            </div>
            <!--End LOGIN block-->
        </div>
    </div>    
</body>
</html>