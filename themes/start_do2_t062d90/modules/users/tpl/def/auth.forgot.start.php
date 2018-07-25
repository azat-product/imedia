<?php
/**
 * Восстановление пароля: Шаг 1
 * @var $this Users
 * @var $social integer инициировано ли восстановление на этапе авторизации через соц. сеть
 */
?>

<form action="" id="j-u-forgot-start-form-<?= bff::DEVICE_DESKTOP ?>" class="form-horizontal mrgt30 mrgb30">
  <input type="hidden" name="social" value="<?= $social ?>" />
  <div class="form-group">
    <label for="j-u-forgot-start-desktop-email" class="col-sm-4 control-label"><?= _t('users', 'Электронная почта') ?></label>
    <div class="col-md-4 col-sm-4 mrgb10">
      <input class="form-control j-required" type="email" name="email" id="j-u-forgot-start-desktop-email" placeholder="<?= _te('users', 'Введите ваш email') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
    </div>
    <div class="col-sm-4">
      <button type="submit" class="btn btn-default"><?= _t('users', 'Восстановить пароль') ?></button>
    </div>
  </div>
</form>

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
  </div>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jUserAuth.forgotStart(<?= func::php2js(array(
      'lang' => array(
        'email' => _t('users', 'E-mail адрес указан некорректно'),
        'success' => _t('users', 'На ваш электронный ящик были высланы инструкции по смене пароля.'),
        ),
      )) ?>);
  });
  <?php js::stop(); ?>
</script>