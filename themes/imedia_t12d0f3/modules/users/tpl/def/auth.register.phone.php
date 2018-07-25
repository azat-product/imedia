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
  
  <form action="" class="form-horizontal mrgt30 mrgb30">
    <div class="form-group">
      <label for="phone-code-input" class="col-md-5 col-sm-4 control-label"><?= _t('users', 'Код подтверждения') ?></label>
      <div class="col-md-2 col-sm-4 mrgb10">
        <input type="text" class="form-control j-u-register-phone-code-input" id="phone-code-input" placeholder="<?= _te('users', 'Введите код') ?>" />
      </div>
      <div class="col-md-5 col-sm-4">
        <button type="submit" class="btn btn-default j-u-register-phone-code-validate-btn"><?= _t('users', 'Подтвердить') ?></button>
      </div>
    </div>
  </form>

  <div class="text-center">
    <ul class="list-unstyled">
      <li class="mrgb10"><a href="#" class="link-ajax j-u-register-phone-change-step1-btn"><span><?= _t('users', 'Изменить номер телефона') ?></span></a></li>
      <li class="mrgb10"><a href="#" class="link-ajax j-u-register-phone-code-resend-btn"><span><?= _t('users', 'Выслать новый код подтверждения') ?></span></a></li>
    </ul>
  </div>

</div>

<div id="j-u-register-phone-block-phone" style="display: none;">
  
  <form action="" class="form-horizontal mrgt30 mrgb30">
    <div class="form-group">
      <label class="col-sm-4 control-label"><?= _t('users', 'Номер телефона') ?></label>
      <div class="col-sm-4 mrgb10">
        <?= $this->registerPhoneInput(array('name'=>'phone', 'id'=>'j-u-register-phone-input')) ?>
      </div>
      <div class="col-sm-4">
        <button type="button" class="btn btn-default j-u-register-phone-change-step2-btn"><?= _t('users', 'Выслать код') ?></button>
      </div>
    </div>
  </form>

</div>
<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jUserAuth.registerPhone(<?= func::php2js(array(
      'lang' => array(
        'resend_success' => _t('users', 'Код подтверждения был успешно отправлен повторно'),
        'change_success' => _t('users', 'Код подтверждения был отправлен на указанный вами номер'),
        ),
      )) ?>);
  });
  <?php js::stop(); ?>
</script>