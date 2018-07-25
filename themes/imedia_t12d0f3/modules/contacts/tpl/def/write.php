<?php
/**
 * Форма контактов
 * @var $this Contacts
 * @var $breadcrumb string хлебная крошка или пустая строка
 * @var $captcha_on boolean включена ли капча
 * @var $user array данные о текущем пользователе: name, email
 * @var $types array варианты темы сообщения
 */
tpl::includeJS('contacts.form', false, 2);
$user = HTML::escape($user);
$captcha_url = ( $captcha_on ? tpl::captchaURL('math', array('bg'=>'FFFFFF')) : '' );
?>

<?php if (DEVICE_DESKTOP_OR_TABLET) {
  echo tpl::getBreadcrumbs(array( array('title'=>(!empty($breadcrumb) ? $breadcrumb : config::get('contacts_form_title_'.LNG, _t('contacts', 'Контакты'))), 'active'=>true) ));
} ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= config::get('contacts_form_title_'.LNG, _t('contacts', 'Контакты')) ?></h1>
    </div>
    <?= config::get('contacts_form_text_'.LNG) ?>
    
    <div class="row">
      <div class="col-md-6">
        <form id="j-contacts-form" action="" class="form-horizontal">
          <h2 class="l-pageSubheading"><?= config::get('contacts_form_title2_'.LNG) ?></h2>
          <div class="form-group">
            <label for="j-contacts-form-name" class="col-sm-4 control-label"><?= _t('contacts', 'Ваше имя') ?><span class="required-mark">*</span></label>
            <div class="col-sm-8">
              <input type="text" class="j-required form-control" name="name" id="j-contacts-form-name" value="<?= $user['name'] ?>" maxlength="70" />
            </div>
          </div>
          <div class="form-group">
            <label for="j-contacts-form-email" class="col-sm-4 control-label"><?= _t('contacts', 'Ваш e-mail адрес') ?><span class="required-mark">*</span></label>
            <div class="col-sm-8">
              <input type="email" class="j-required form-control" name="email" id="j-contacts-form-email" value="<?= $user['email'] ?>" maxlength="70" />
            </div>
          </div>
          <div class="form-group">
            <label for="j-contacts-form-subject" class="col-sm-4 control-label"><?= _t('contacts', 'Выберите тему') ?><span class="required-mark">*</span></label>
            <div class="col-sm-8">
              <select name="ctype" class="j-required form-control" id="j-contacts-form-subject"><?= $types ?></select>
            </div>
          </div>
          <div class="form-group">
            <label for="j-contacts-form-message" class="col-sm-4 control-label"><?= _t('contacts', 'Сообщение') ?><span class="required-mark">*</span></label>
            <div class="col-sm-8">
              <textarea name="message" class="input-block-level j-required form-control" id="j-contacts-form-message" rows="5" autocapitalize="off"></textarea>
            </div>
          </div>
          <?php if( ! User::id() && $captcha_on ) { ?>
            <?php if (Site::captchaCustom('contacts-write')) { ?>
              <div class="form-group">
                <?php bff::hook('captcha.custom.view', 'contacts-write', __FILE__); ?>
              </div>
            <?php } else { ?>
              <div class="form-group">
                <label for="j-contacts-form-captcha" class="col-sm-4 control-label"><?= _t('contacts', 'Введите результат') ?></label>
                <div class="col-sm-4">
                  <input type="text" name="captcha" class="j-required j-captcha form-control" id="j-contacts-form-captcha" pattern="[0-9]*" />
                </div>
                <div class="col-sm-4">
                  <img src="<?= $captcha_url ?>" style="cursor: pointer;" onclick="jContactsForm.refreshCaptha();" id="j-contacts-form-captcha-code" alt="" />
                </div>
              </div>
            <?php } ?>
          <?php } ?>

          <div class="form-group">
            <div class="col-sm-8 col-sm-offset-4">
              <button type="submit" name="" class="btn btn-success"><i class="fa fa-envelope white"></i> <?= _t('contacts', 'Отправить сообщение') ?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jContactsForm.init(<?= func::php2js(array(
      'captcha' => !empty($captcha_on),
      'captcha_url' => $captcha_url,
      'submit_url' => Contacts::url('form'),
      'lang' => array(
        'email' => _t('', 'E-mail адрес указан некорректно'),
        'message' => _t('contacts', 'Текст сообщения слишком короткий'),
        'captcha' => _t('', 'Введите результат с картинки'),
        'success' => _t('contacts', 'Ваше сообщение успешно отправлено.<br/>Мы постараемся ответить на него как можно скорее.'),
        ),
      )) ?>);
  });
  <?php js::stop(); ?>
</script>