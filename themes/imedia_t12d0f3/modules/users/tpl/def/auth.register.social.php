<?php
/**
 * Регистрация через соц. сети
 * @var $this Users
 * @var $avatar string URL аватара полученного от соц. сети
 * @var $name string имя
 * @var $email string email
 */
?>
<div class="l-table">
  <div class="l-table-row">
    <div class="u-authorize-form u-authorize-form_soc l-table-cell">
      <form id="j-u-register-social-form" action="" class="form-horizontal">
        <div class="u-authorize-form_soc__info control-group">
          <div class="control-label">
            <span><img alt="" width="65" height="65" class="img-circle" src="<?= $avatar ?>" /></span>
          </div>
          <div class="controls j-social"><?= _t('users','Здравствуйте, <b>[name]</b>!', array('name'=>$name)) ?></div>
          <div class="controls hide j-social"><?= _t('users', 'У вас уже есть профиль на [site_name]?', array('site_name'=>Site::title('users.register.social'))) ?></div>
        </div>
        <div class="control-group">
          <div class="controls">
            <input type="text" name="email" value="<?= ( ! empty($email) ? HTML::escape($email) : '' ) ?>" class="j-required" id="j-u-register-social-email" placeholder="<?= _te('users','Введите ваш email') ?>" maxlength="100" />
            <a class="additions pseudo-link-ajax j-social" style="display:none;" id="j-u-register-social-email-change" href="#"><?= _t('users','Изменить e-mail') ?></a>
          </div>
        </div>
        <div class="control-group hide j-social">
          <div class="controls">
            <input type="password" name="pass" id="j-u-register-social-pass" placeholder="<?= _te('users','Введите ваш пароль') ?>" maxlength="100" />
            <a href="<?= Users::url('forgot', array('social'=>1)) ?>" class="additions"><?= _t('users','Забыли пароль?') ?></a>
          </div>
        </div>
        <div class="control-group j-social">
          <div class="controls">
            <label class="checkbox">
              <input type="checkbox" name="agreement" id="j-u-register-social-agreement" autocomplete="off" /> <small><?= _t('users', 'Я соглашаюсь с <a href="[link_agreement]" target="_blank">правилами использования сервиса</a>, а также с передачей и обработкой моих данных.', array('link_agreement'=>Users::url('agreement'))) ?><span class="required-mark">*</span></small>
            </label>
          </div>
        </div>
        <div class="control-group">
          <div class="controls">
            <button type="submit" class="btn btn-success j-social"><?= _t('users','Завершить регистрацию') ?></button>
            <button type="submit" class="btn hide j-social"><?= _t('users','Объединить профили') ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jUserAuth.registerSocial(<?= func::php2js(array(
      'login_url' => Users::url('login'),
      'lang' => array(
        'register'=>array(
          'title' => _t('users', 'Для завершения регистрации введите Вашу электронную почту'),
          'email' => _t('users', 'E-mail адрес указан некорректно'),
          'agreement' => _t('users', 'Пожалуйста подтвердите, что Вы согласны с пользовательским соглашением'),
          ),
        'login'=>array(
          'title' => _t('users', 'Пользователь с таким e-mail адресом уже зарегистрирован.'),
          'pass' => _t('users', 'Укажите пароль'),
          )
        ),
      )) ?>);
  });
  <?php js::stop(); ?>
</script>