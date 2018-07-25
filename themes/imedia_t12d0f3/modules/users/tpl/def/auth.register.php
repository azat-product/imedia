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

<div class="row">
  <div class="col-sm-6">
    <form class="form-horizontal" id="j-u-register-form" action="">
      <input type="hidden" name="back" value="<?= HTML::escape($back) ?>" />
      <?php if($phone_on) { ?>
      <div class="form-group">
        <label class="col-md-6 control-label" for="j-u-register-phone"><?= _t('users', 'Телефон') ?><span class="required-mark">*</span></label>
        <div class="col-md-6">
          <?= $this->registerPhoneInput(array('id'=>'j-u-register-phone','name'=>'phone')) ?>
        </div>
      </div>
      <?php } ?>
      <div class="form-group">
        <label class="col-md-6 control-label" for="j-u-register-email"><?= _t('users', 'Электронная почта') ?><span class="required-mark">*</span></label>
        <div class="col-md-6">
          <input type="email" name="email" class="form-control j-required" id="j-u-register-email" autocomplete="off" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-6 control-label" for="j-u-register-pass"><?= _t('users', 'Пароль') ?><span class="required-mark">*</span></label>
        <div class="col-md-6">
          <input type="password" name="pass" class="form-control j-required" id="j-u-register-pass" autocomplete="off" placeholder="<?= _te('users', 'Введите ваш пароль') ?>" maxlength="100" />
        </div>
      </div>
      <?php if($pass_confirm_on) { ?>
      <div class="form-group">
        <label class="col-md-6 control-label" for="j-u-register-pass2"><?= _t('users', 'Повторите пароль') ?><span class="required-mark">*</span></label>
        <div class="col-md-6">
          <input type="password" name="pass2" class="form-control j-required" id="j-u-register-pass2" autocomplete="off" placeholder="<?= _te('users', 'Введите пароль ещё раз') ?>" maxlength="100" />
        </div>
      </div>
      <?php } ?>
      <?php if($captcha_on) { ?>
        <?php if (Site::captchaCustom('users-auth-register')) { ?>
          <div class="form-group">
            <?php bff::hook('captcha.custom.view', 'users-auth-register', __FILE__); ?>
          </div>
        <?php } else { ?>
          <div class="form-group">
            <label class="col-md-6 control-label" for="j-u-register-captcha"><?= _t('users', 'Результат с картинки') ?><span class="required-mark">*</span></label>
            <div class="col-md-6">
              <div class="row">
                <div class="col-xs-6">
                  <input type="text" name="captcha" id="j-u-register-captcha" autocomplete="off" class="form-control j-required" value="" pattern="[0-9]*" />
                </div>
                <div class="col-xs-6">
                  <img src="<?= tpl::captchaURL() ?>" class="j-captcha" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&rnd='+Math.random())" />
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
      <?php } ?>
      <div class="form-group">
      <div class="col-md-offset-6 col-md-6">
        <div class="checkbox">
          <label>
            <input type="checkbox" name="agreement" id="j-u-register-agreement" autocomplete="off" /> <?= _t('users', 'Я соглашаюсь с <a href="[link_agreement]" target="_blank">правилами использования сервиса</a>, а также с передачей и обработкой моих данных.', array('link_agreement'=>Users::url('agreement'))) ?><span class="required-mark">*</span>
          </label>
        </div>
      </div>
    </div>
      <div class="form-group">
        <div class="col-md-offset-6 col-md-6">
          <button type="submit" class="btn btn-default j-submit"><?= _t('users', 'Зарегистрироваться') ?></button>
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
        <strong><?= _t('users', 'Вы уже зарегистрированы?') ?></strong>
      </div>
      <div class="usr-signBlock-r">
        <a href="<?= Users::url('login') ?>" class="btn btn-primary"><?= _t('users', 'Войдите на сайт') ?></a>
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
  <?php js::stop(); ?>
</script>