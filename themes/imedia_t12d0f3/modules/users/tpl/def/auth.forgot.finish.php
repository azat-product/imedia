<?php
/**
 * Восстановление пароля: Шаг 2
 * @var $this Users
 * @var $key string ключ восстановления
 * @var $social integer инициировано ли восстановление на этапе авторизации через соц. сеть
 */
?>

<form action="" id="j-u-forgot-finish-form-<?= bff::DEVICE_DESKTOP ?>" class="form-horizontal mrgt30 mrgb30">
  <input type="hidden" name="key" value="<?= HTML::escape($key) ?>" />
  <input type="hidden" name="social" value="<?= $social ?>" />
  <div class="form-group">
    <label for="j-u-forgot-finish-desktop-pass" class="col-sm-4 control-label"><?= _t('users', 'Новый пароль') ?></label>
    <div class="col-md-4 col-sm-4 mrgb10">
      <input type="password" name="pass" class="form-control j-required" id="j-u-forgot-finish-desktop-pass" placeholder="<?= _te('users', 'Введите пароль') ?>" maxlength="100" />
    </div>
    <div class="col-sm-4">
      <button type="submit" class="btn btn-default"><?= _t('users', 'Изменить пароль') ?></button>
    </div>
  </div>
</form>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jUserAuth.forgotFinish(<?= func::php2js(array(
      'lang' => array(
        'pass' => _t('users', 'Укажите пароль'),
        'success' => _t('users', 'Ваш пароль был успешно изменен.'),
        ),
      )) ?>);
  });
  <?php js::stop(); ?>
</script>