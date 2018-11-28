<?php
/**
 * Авторизация
 * @var $this Users
 * @var $back string URL страницы возврата (после успешной авторизации)
 * @var $providers array провайдеры авторизации через соц. сети
 */
?>

<div class="row">
  <div class="col-sm-6">
    <form class="form-horizontal" id="j-u-login-form" action="">
      <input type="text" name="back" value="<?= HTML::escape($back) ?>" />
      <div class="form-group">
        <label class="col-md-6 control-label" for="j-u-login-email"><?= _t('users', 'Электронная почта') ?><span class="required-mark">*</span></label>
        <div class="col-md-6">
          <input type="email" name="email" class="form-control" id="j-u-login-email" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-6 control-label" for="j-u-login-pass"><?= _t('users', 'Пароль') ?><span class="required-mark">*</span></label>
        <div class="col-md-6">
          <input type="password" name="pass" class="form-control" id="j-u-login-pass" placeholder="<?= _te('users', 'Введите ваш пароль') ?>" maxlength="100" />
        </div>
      </div>
      <?php if(Users::loginRemember()) { ?>
      <div class="form-group">
        <div class="col-md-offset-6 col-md-6">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="remember"/> <?= _t('users', 'Запомнить меня'); ?>
            </label>
          </div>
        </div>
      </div>
      <?php } ?>
      <div class="form-group">
        <div class="col-md-offset-6 col-md-6">
          <button type="submit" class="btn btn-default j-submit"><?= _t('users', 'Войти на сайт') ?></button>
        </div>
      </div>
    </form>
  </div><!-- /.col-sm-6 -->
  <div class="col-sm-6">
    <?php foreach($providers as $v) {

      ?><a href="#" class="btn btn-sm btn-social btn-<?= $v['class'] ?> j-u-login-social-btn" data="{provider:'<?= $v['key'] ?>',w:<?= $v['w'] ?>,h:<?= $v['h'] ?>}"><?= $v['title'] ?></a><?php

    } ?>
  </div><!-- /.col-sm-6 -->
</div><!-- /.row -->

<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="usr-signBlock">
      <div class="usr-signBlock-l">
        <strong><?= _t('users', 'Впервые на нашем сайте?') ?></strong>
      </div>
      <div class="usr-signBlock-r">
        <a href="<?= Users::url('register') ?>" class="btn btn-primary"><?= _t('users', 'Зарегистрируйтесь') ?></a>
      </div>
    </div>
    <div class="usr-signBlock">
      <div class="usr-signBlock-l">
        <strong><?= _t('users', 'Вы забыли свой пароль?') ?></strong>
      </div>
      <div class="usr-signBlock-r">
        <a href="<?= Users::url('forgot') ?>" class="btn btn-default"><?= _t('users', 'Восстановить пароль') ?></a>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
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
  <?php js::stop(); ?>
</script>