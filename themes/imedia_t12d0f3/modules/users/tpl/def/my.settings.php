<?php
/**
 * Кабинет пользователя: Настройки
 * @var $this Users
 * @var $shop_opened boolean открыт ли магазин
 * @var $shop_id integer ID магазина (если был открыт)
 * @var $on array настройки формы: 'contacts' - контакты, 'phone' - редактирование номера телефона, 'email' - редактирование email
 * @var $social array провайдеры авторизации через соц. сети
 * @var $enotify array подписка на уведомления от сервиса
 */
Geo::mapsAPI(true);
tpl::includeJS(array('qquploader'), true);
tpl::includeJS('users.my', false, 4);
$aData = HTML::escape($aData, 'html', array('email', 'phone_number', 'name', 'addr_addr', 'region_title'));
?>

<div class="usr-settings" id="j-my-settings">
  <?php if ($shop_opened && Shops::abonementEnabled()): ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="abonement">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Настройки тарифа магазина') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-abonement">
        <?= Shops::i()->my_abonement(); ?>
      </div>
    </div>
  <?php endif; # shop abonement ?>
  <?php if ($shop_opened): ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="shop">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Настройки магазина') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-shop">
        <?= Shops::i()->my_settings() ?>
      </div>
    </div>
  <?php endif; # shop settings ?>
  <?php if ($on['contacts']) { ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="contacts">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Контактные данные') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-contacts">
        <form class="form-horizontal j-form-contacts" action="">
          <input type="hidden" name="act" value="contacts"/>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Контактное лицо') ?><span
                class="required-mark">*</span></label>
            <div class="col-md-3 col-sm-3">
              <input type="text" name="name" value="<?= $name ?>" class="form-control j-required" maxlength="50"/>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Фотография') ?><br/>
              <small><?= _t('users', 'Профили с личной фотографией пользуются большим доверием') ?></small>
            </label>
            <div class="col-sm-9">
              <div class="usr-settings-photo">
                <img alt="" class="img-circle" src="<?= $avatar_normal ?>" id="j-my-avatar-img"/>
              </div>
              <div class="usr-settings-photo-upload">
                <a class="link-ajax" id="j-my-avatar-upload"
                   href="javascript:void(0);"><span><?= _t('users', 'загрузить фото') ?></span></a>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Контакты') ?></label>
            <div class="col-md-3 col-sm-6">
              <div class="form-group-item" id="j-my-phones"></div>
              <?php foreach (Users::contactsFields() as $contact): ?>
                <div class="form-group-item">
                  <div class="input-group">
                    <span class="input-group-addon"><i class="<?= $contact['icon'] ?>"></i></span>
                    <input type="text" class="form-control" name="contacts[<?= $contact['key'] ?>]"
                           value="<?= isset($contacts[$contact['key']]) ? HTML::escape($contacts[$contact['key']]) : '' ?>"
                           placeholder="<?= $contact['title'] ?>"
                           maxlength="<?= $contact['maxlength'] ?>"/>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div id="j-my-geo">
            <div class="form-group">
              <label class="col-sm-3 control-label"><?= _t('users', 'Город') ?></label>
              <div class="col-md-6 col-sm-9">
                <?= Geo::i()->citySelect($region_id, true, 'region_id', array(
                  'on_change' => 'jMySettings.onCitySelect',
                  'form' => 'users-settings',
                )); ?>
              </div>
            </div>
            <div id="j-my-geo-addr">
              <div class="form-group">
                <label class="col-sm-3 control-label"><?= _t('users', 'Точный адрес') ?></label>
                <div class="col-md-6 col-sm-9">
                  <input type="hidden" name="addr_lat" id="j-my-geo-addr-lat" value="<?= $addr_lat ?>"/>
                  <input type="hidden" name="addr_lon" id="j-my-geo-addr-lon" value="<?= $addr_lon ?>"/>
                  <input type="text" class="form-control" name="addr_addr" id="j-my-geo-addr-addr"
                         value="<?= $addr_addr ?>"/>
                </div>
              </div>
              <div class="form-group">
                <div class="col-md-6 col-sm-9 col-sm-offset-3">
                  <div id="j-my-geo-addr-map" class="i-formpage__map_desktop" style="height: 250px; width: 100%;"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="col-md-6 col-sm-9 col-sm-offset-3">
              <input type="submit" class="btn btn-success" value="<?= _te('users', 'Сохранить') ?>"/>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php } # on['contacts'] ?>
  <?php if (!empty($social)) { ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="social">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Связь с социальными сетями') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-social">
        <form action="">
          <div class="help-block">
            <?= _t('users', 'Для ускорение процесса авторизации вы можете использовать <br />свои аккаунты в социальных сетях') ?>
            :
          </div>
          <?php foreach ($social as $k => $v) {

            ?>
            <a href="#"
               class="btn btn-sm btn-social btn-<?= $v['class'] ?><?php if (isset($v['user'])) { ?> active<?php } ?> j-my-social-btn"
               data="{provider:'<?= $v['key'] ?>',w:<?= $v['w'] ?>,h:<?= $v['h'] ?>}"><?= $v['title'] ?></a>
            <?php

          } ?>

        </form>
      </div>
    </div>
  <?php } ?>
  <?php if (!empty($enotify)) { ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="enotify">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Настройка уведомлений') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-enotify">
        <form class="form-horizontal j-form-enotify" action="">
          <input type="hidden" name="act" value="enotify"/>
          <?php foreach ($enotify as $k => $v) { ?>
            <div class="checkbox">
              <label><input type="checkbox" name="enotify[]"
                            value="<?= $k ?>" <?php if ($v['a']) { ?> checked="checked"<?php } ?>
                            class="j-my-enotify-check"/><?= $v['title'] ?></label>
            </div>
          <?php } ?>
        </form>
      </div>
    </div>
  <?php } ?>
  <div class="usr-settings-box">
    <div class="usr-settings-box-title">
      <a href="javascript:void(0);" class="j-block-toggler" data-block="pass">
        <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
        <?= _t('users', 'Изменить пароль') ?>
      </a>
    </div>
    <div class="usr-settings-box-content hide j-block j-block-pass">
      <form class="form-horizontal j-form-pass" action="">
        <input type="hidden" name="act" value="pass"/>
        <div class="form-group">
          <label class="col-sm-3 control-label"><?= _t('users', 'Текущий пароль') ?><span class="required-mark">*</span></label>
          <div class="col-md-3 col-sm-6">
            <input type="password" class="form-control j-required" name="pass0" maxlength="100" autocomplete="off"/>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label"><?= _t('users', 'Новый пароль') ?><span
              class="required-mark">*</span></label>
          <div class="col-md-3 col-sm-6">
            <input type="password" class="form-control j-required" name="pass1" maxlength="100"/>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label"><?= _t('users', 'Новый пароль еще раз') ?><span
              class="required-mark">*</span></label>
          <div class="col-md-3 col-sm-6">
            <input type="password" class="form-control j-required" name="pass2" maxlength="100"/>
          </div>
        </div>
        <div class="form-group">
          <div class="col-sm-9 col-sm-offset-3">
            <input type="submit" class="btn btn-success" value="<?= _te('users', 'Изменить пароль') ?>"/>
          </div>
        </div>
      </form>
    </div>
  </div>
  <?php if ($on['phone']) { ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="phone">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Изменить номер телефона') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-phone">
        <form class="form-horizontal j-form-phone" action="">
          <input type="hidden" name="act" value="phone"/>
          <input type="hidden" name="save" value=""/>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Ваш номер') ?>:</label>
            <div class="col-md-3 col-sm-6">
              <div class="input-group">
                <input type="text" class="form-control" name="phone0"
                       value="<?= (!empty($phone_number) ? '+' . $phone_number : _t('users', 'Не указан')) ?>"
                       disabled="disabled"/>
                <?php if ($phone_number_verified && !empty($phone_number)) { ?>
                  <span class="input-group-addon">
                <i class="fa fa-check text-success hidden-xs"></i>
              </span>
                <?php } ?>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Новый номер') ?><span class="required-mark">*</span></label>
            <div class="col-md-3 col-sm-6 j-phone-change-step1">
              <?= $this->registerPhoneInput(array('name' => 'phone', 'autocomplete' => 'off')) ?>
            </div>
            <div class="col-md-3 col-sm-6 j-phone-change-step2" style="display: none;">
              <input type="text" name="code" class="form-control j-phone-change-code"
                     placeholder="<?= _te('users', 'Введите код из sms') ?>"/>
              <div class="mrgt10">
                <a href="#"
                   class="link-ajax j-phone-change-repeate"><span><?= _t('users', 'Выслать код повторно') ?></span></a><br>
                <a href="#" class="link-ajax j-phone-change-back"><span><?= _t('users', 'Изменить номер') ?></span></a>
              </div>
              <div class="alert alert-warning mrgb0 mrgt10" style="display: none;"></div>
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-9 col-sm-offset-3">
              <input type="submit" class="btn btn-success" value="<?= _te('users', 'Изменить') ?>"/>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php } ?>
  <?php if ($on['email']) { ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="email">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Изменить email-адрес') ?>
        </a>
      </div>
      <div class="usr-settings-box-content hide j-block j-block-email">
        <form class="form-horizontal j-form-email" action="">
          <input type="hidden" name="act" value="email"/>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Ваш текущий email') ?>:</label>
            <div class="col-md-3 col-sm-6">
              <input type="email" class="form-control" name="email0" value="<?= $email ?>" disabled="disabled"/>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Новый email') ?><span class="required-mark">*</span></label>
            <div class="col-md-3 col-sm-6">
              <input type="email" class="form-control j-required" name="email" maxlength="100"/>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Текущий пароль') ?><span
                class="required-mark">*</span></label>
            <div class="col-md-3 col-sm-6">
              <input type="password" class="form-control j-required" name="pass" maxlength="100" autocomplete="off"/>
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-9 col-sm-offset-3">
              <input type="submit" class="btn btn-success" value="<?= _te('users', 'Изменить') ?>"/>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php } ?>
  <?php if ($on['destroy']) { ?>
    <div class="usr-settings-box">
      <div class="usr-settings-box-title">
        <a href="javascript:void(0);" class="j-block-toggler" data-block="destroy">
          <i class="fa fa-chevron-down usr-settings-box-title-icon j-icon"></i>
          <?= _t('users', 'Удалить учетную запись') ?>
        </a>
      </div>
      <div class="usr-settings-box-content  j-block j-block-destroy">
        <form class="form-horizontal j-form-destroy" action="">
          <input type="hidden" name="act" value="destroy"/>
          <div class="help-block">
            <?= _t('users', 'Вы можете удалить свою учетную запись, если больше не планируете пользоваться сайтом') ?>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label"><?= _t('users', 'Текущий пароль') ?><span
                class="required-mark">*</span></label>
            <div class="col-md-3 col-sm-6">
              <input type="password" class="form-control j-required" name="pass" maxlength="100" autocomplete="off"/>
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-9 col-sm-offset-3">
              <input type="submit" class="btn btn-danger" value="<?= _te('users', 'Удалить учетную запись') ?>"/>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php } ?>
  <script type="text/html" class="j-tpl-user-phones">
    <div class="form-group-item">
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-phone"></i></span>
        <input type="tel" maxlength="30" name="phones[<%= index %>]" value="<%= value %>" class="form-control"
               placeholder="<%= o.lang.phones_tip %>"/>
      </div>
      <% if(plus) { %>
      <div class="mrgt5"><a class="link-ajax j-plus" href="#"><span><%= o.lang.phones_plus %></span></a></div>
      <% } %>
    </div>
  </script>
</div>

<script type="text/javascript">
  <?php js::start() ?>
  jMySettings.init(<?= func::php2js(array(
    'url_settings' => Users::url('my.settings'),
    'url_social' => Users::url('login.social'),
    'lang' => array(
      'saved_success' => _t('', 'Настройки успешно сохранены'),
      'ava_upload_messages' => array(
        'typeError' => _t('users', 'Допустимы только следующие типы файлов: {extensions}'),
        'sizeError' => _t('users', 'Файл {file} слишком большой, максимально допустимый размер {sizeLimit}'),
        'minSizeError' => _t('users', 'Файл {file} имеет некорректный размер'),
        'emptyError' => _t('users', 'Файл {file} имеет некорректный размер'),
        'onLeave' => _t('users', 'Происходит загрузка изоражения, если вы покинете эту страницу, загрузка будет прекращена'),
      ),
      'ava_upload' => _t('users', 'Загрузка фотографии'),
      'phones_tip' => _t('item-form', 'Номер телефона'),
      'phones_plus' => _t('item-form', '+ ещё<span [attr]> телефон</span>', array('attr' => 'class="hidden-xs"')),
      'pass_changed' => _t('users', 'Пароль был успешно изменен'),
      'pass_confirm' => _t('users', 'Ошибка подтверждения пароля'),
      'phone_changed' => _t('users', 'Номер телефона был успешно изменен'),
      'phone_code_sended' => _t('users', 'Код подтверждения был отправлен на указанный номер телефона'),
      'email_wrong' => _t('users', 'E-mail адрес указан некорректно'),
      'email_diff' => _t('users', 'E-mail адрес не должен совпадать с текущим'),
    ),
    # avatar
    'avatarMaxsize' => $avatar_maxsize,
    'avatarSzSmall' => UsersAvatar::szSmall,
    'avatarSzNormal' => UsersAvatar::szNormal,
    # phones
    'phonesLimit' => Users::i()->profilePhonesLimit,
    'phonesData' => $phones,
    # tab
    'tab' => $tab,
    'uploadProgress' => '<div class="align-center j-progress"   style="width: 64px;  height: 64px;  float: left;  line-height: 64px;"> <img alt="" src="' . bff::url('/img/loading.gif') . '"> </div>',
  )) ?>);
  <?php js::stop() ?>
</script>