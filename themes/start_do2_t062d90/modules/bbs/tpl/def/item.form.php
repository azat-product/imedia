<?php

/**
 * Форма объявления: добавление / редактирование
 * @var $this BBS
 * @var $id integer ID редактируемого объявления или 0 (при добавлении)
 * @var $userID integer ID текущего авторизованного пользователя или 0
 * @var $edit boolean редактирование (true), добавление (false)
 * @var $img BBSItemImages компонент работы с изображениями
 * @var $imagesLimit integer лимит кол-во загружаемых изображений
 * @var $imagesUploaded integer кол-во ранее загруженных изображений (при редактировании)
 * @var $contactsFromProfile boolean контакты берутся из профиля пользователя (true)
 * @var $lang array текстовые фразы
 */

Geo::mapsAPI(true);
tpl::includeJS(array('autocomplete', 'ui.sortable', 'qquploader'), true);
tpl::includeJS('bbs.form', false, 12);
$aData = HTML::escape($aData, 'html', array('title', 'descr', 'addr_addr', 'name', 'video'));
$autoTitle = !empty($cat_data['tpl_title_enabled']);
?>

<?= tpl::getBreadcrumbs($breadcrumbs); ?>

<div class="l-pageHeading">
  <h1 class="l-pageHeading-title">
    <?= $h1 ?>
  </h1>
</div>

<?php // Editing
if ($edit) { ?>

  <?php switch ($status) {
    // Blocked
    case BBS::STATUS_BLOCKED: { ?>
      <div class="alert alert-danger rel">
        <?= _t('bbs', 'Объявление было заблокировано модератором.') ?><br/>
        <?= _t('bbs', 'Причина блокировки:') ?>&nbsp;<strong><?= $blocked_reason ?></strong>
      </div>
    <?php }
      break;
    // Publicated
    case BBS::STATUS_PUBLICATED: {
      if ((strtotime($publicated_to) - BFF_NOW) < 172800 /* менее 2 дней */) { ?>
        <div class="alert alert-info">
          <div><?= _t('bbs', 'Объявление опубликовано') ?> <?= _t('bbs', 'до [date]', array('date' => tpl::date_format2($publicated_to, true))) ?></div>
          <a href="#" class="btn btn-info alert-action mrgt10" id="j-i-form-action-refresh"><i
              class="fa fa-refresh white"></i> <?= _t('bbs', 'Продлить') ?></a>
        </div>
      <?php }
    }
      break;
    // Publicated Out
    case BBS::STATUS_PUBLICATED_OUT: { ?>
      <div class="alert alert-info rel">
        <div><?= _t('bbs', 'Объявление снято с публикации') ?> <?= tpl::date_format2($publicated_to, true) ?></div>
        <a href="#" class="btn btn-info alert-action mrgt10" id="j-i-form-action-publicate"><i
            class="fa fa-check white"></i> <?= _t('bbs', 'Опубликовать снова') ?></a>
      </div>
    <?php }
      break;
  } # $status ?>

<?php } # $edit ?>

<form class="form-horizontal" action="" id="j-i-form" method="POST" enctype="multipart/form-data">

  <?php if ($publisher == BBS::PUBLISHER_USER_OR_SHOP && $shop): ?>
    <div class="form-group">
      <label class="col-sm-3 control-label"><?= _t('item-form', 'Разместить как') ?><span class="required-mark">*</span></label>
      <div class="col-md-6 col-sm-9">
        <input type="hidden" name="shop" value="<?= ($shop_id ? 1 : 0) ?>" class="j-publisher-type"/>
        <div class="btn-group">
          <?php
          foreach (array(
                     array('id' => 0, 't' => _t('item-form', 'Частное лицо'), 'a' => (!$shop_id), 'd' => false),
                     array('id' => 1, 't' => _t('item-form', 'Магазин'), 'a' => ($shop_id), 'd' => (!$shop_data && !$shop_id)),
                   ) as $v):
            ?>
            <button type="button" class="btn btn-default<?php if ($v['a']) { ?> active<?php }
          if ($v['d']) { ?> disabled<?php } ?> j-publisher-toggle" data-type="<?= $v['id'] ?>"><?= $v['t'] ?></button><?php
          endforeach;
          ?>
          <?php if (!$shop_data) { ?>
            <div class="alert alert-info">
              <?= _t('item-form', 'Ваш магазин был <a [link]>деактивирован или заблокирован</a>.<br/>Невозможно разместить объявление от магазина.', array(
                'link' => 'href="' . Shops::url('my.shop') . '" target="_blank"'
              )); ?>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  <?php else: if ($publisher == BBS::PUBLISHER_USER_TO_SHOP && $shop): ?>
    <input type="hidden" name="shop" value="1" class="j-publisher-type"/>
  <?php endif; ?>
  <?php endif; # $publisher == BBS::PUBLISHER_USER_OR_SHOP ?>

  <div class="form-group">
    <label for="j-i-title" class="col-sm-3 control-label"><?= _t('item-form', 'Заголовок') ?><span
        class="required-mark">*</span></label>
    <div class="col-md-6 col-sm-9">
      <input type="text" name="title" value="<?= $title ?>" class="form-control j-required" id="j-i-title"
             autocomplete="off" data-limit="<?= $titleLimit ?>" <?= $autoTitle ? 'disabled="disabled"' : '' ?>>
      <div class="help-block<?=  $autoTitle ? ' hidden' : '' ?>" id="j-i-title-maxlength"></div>
      <div class="help-block<?= !$autoTitle ? ' hidden' : '' ?>" id="j-i-title-auto"><?= _te('item-form', 'Заголовок будет сгенерирован автоматически на основе указанных вами данных') ?></div>
    </div>
  </div>

  <div class="form-group">
    <label class="col-sm-3 control-label"><?= _t('item-form', 'Категория') ?><span
        class="required-mark">*</span></label>
    <div class="col-md-6 col-sm-9">
      <input type="hidden" name="cat_id" class="j-cat-value j-required" value="<?= $cat_id ?>"/>
      <div class="dropdown">
        <div class="j-cat-select-link-selected"<?php if (!$cat_id) { ?> style="display: none;"<?php } ?>>
          <img class="j-icon" alt="" src="<?= ($cat_id ? $cat_data['icon'] : '') ?>"/>
          <a href="#" class="j-cat-select-link j-title"><?= join(' &raquo; ', $cat_path) ?></a>
          <?php if ($edit && !BBS::categoryFormEditable()) { ?>
            <div class="alert alert-info mrgt10 mrgb0">
              <?= _t('item-form', 'Ваше объявление было закреплено за этой категорией.<br />Вы не можете изменить её.') ?>
            </div>
          <?php } ?>
        </div>
        <div class="form-group-noinput j-cat-select-link-empty"<?php if ($cat_id) { ?> style="display: none;"<?php } ?>>
          <a href="#" class="link-ajax j-cat-select-link"><span><?= _t('item-form', 'Выберите категорию') ?></span> <i
              class="fa fa-chevron-down"></i></a>
        </div>
        <div class="dropdown-menu j-cat-select-popup">
          <div class="j-cat-select-step1-desktop">
            <?= $this->catsList('form', bff::DEVICE_DESKTOP, 0); ?>
          </div>
          <div class="j-cat-select-step2-desktop hide"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="j-cat-form hide">
    <?= ($cat_id ? $cat_data['form'] : '') ?>
  </div>
  <div class="form-group">
    <label for="j-i-descr" class="col-sm-3 control-label"><?= _t('item-form', 'Описание') ?><span class="required-mark">*</span></label>
    <div class="col-md-6 col-sm-9">
      <textarea name="descr" class="form-control j-required" id="j-i-descr" data-limit="<?= $descrLimit ?>" rows="6"
                autocapitalize="off"><?= $descr ?></textarea>
      <div class="help-block" id="j-i-descr-maxlength"></div>
    </div>
  </div>
  <?php if ($publicationPeriod) { ?>
    <div class="form-group">
      <label for="publication-period" class="col-sm-3 control-label"><?= _t('item-form', 'Срок публикации') ?></label>
      <div class="col-md-3 col-sm-6">
        <select class="form-control" id="publication-period"
                name="publicated_period"><?= HTML::selectOptions($publicationPeriodOpts, $publicationPeriodDays, false, 'days', 't') ?></select>
        <div class="help-block j-period-help">
          <?= _t('item-form', 'до [date]', array('date' => tpl::date_format2(time() + $publicationPeriodDays * 86400, false, true))); ?>
        </div>
      </div>
    </div>
  <?php } ?>
  <div class="form-group j-video">
    <label for="item-video" class="col-sm-3 control-label"><?= _t('item-form', 'Ссылка на видео') ?></label>
    <div class="col-md-6 col-sm-9">
      <input type="text" name="video" value="<?= $video ?>" class="form-control j-video" id="item-video"
             maxlength="1500"/>
      <div class="help-block"><?= _t('item-form', 'Youtube, Rutube, Vimeo') ?></div>
    </div>
  </div>
  <div class="form-group j-images">
    <input type="hidden" name="images_type" value="ajax" class="j-images-type-value"/>
    <input type="hidden" name="images_hash" value="<?= $imghash ?>"/>
    <label class="col-sm-3 control-label"><?= _t('item-form', 'Фотографии') ?><br/>
      <small><?= _t('item-form', 'Объявления с фото получают в среднем в 3-5 раз больше откликов') ?></small>
    </label>
    <div class="col-md-6 col-sm-9 static">

      <div class="j-images-type j-images-type-ajax">
        <ul class="ad-gallery j-img-slots">
          <?php for ($i = 1; $i <= BBS::itemsImagesLimit(); $i++): ?>
            <li class="<?php if ($i == 1) { ?> ad-gallery-first<?php } ?> j-img-slot"<?php if ($i > $imagesLimit) { ?> style="display: none;"<?php } ?>>
              <span class="ad-gallery-item j-img-upload">
                <a class="ad-gallery-item-in j-img-link" href="javascript:void(0);" title="<?= $lang['image_add'] ?>"><i
                    class="fa fa-plus-circle"></i></a>
              </span>
              <span class="ad-gallery-item ad-gallery-item_preview j-img-preview" style="display: none;">
                <a class="ad-gallery-item-del j-img-link" href="#" title="<?= $lang['image_del'] ?>"><i
                    class="fa fa-times"></i></a>
                <a class="ad-gallery-item-rotate j-img-rotate" href="#" title="<?= $lang['image_rotate'] ?>"><i
                    class="fa fa-rotate-right white"></i></a>
                <span class="ad-gallery-item-img">
                  <img src="" alt="" class="j-img-img"/>
                </span>
                <input type="hidden" name="" value="" class="j-img-fn"/>
              </span>
              <span class="ad-gallery-item j-img-loading" style="display: none;">
                <span class="ad-gallery-item-in">
                  <span class="c-loader"></span>
                </span>
              </span>
            </li>
          <?php endfor; ?>
        </ul>
      </div>
      <div class="j-images-type j-images-type-simple" style="display: none;">
        <?php for ($i = 1; $i <= BBS::itemsImagesLimit(); $i++): ?>
          <input type="file" name="images_simple_<?= $i ?>" class="j-img-slot" <?php if ($i > $imagesLimit) { ?> style="display: none;"<?php } ?> />
        <?php endfor; ?>
      </div>
      <div class="help-block">
        <span
          class="j-togglers"><?= _t('item-form', 'Если у вас возникли проблемы воспользуйтесь <a [a_simple]>альтернативной формой</a>', array('a_simple' => ' href="#" class="link-ajax-nospan j-toggler" data-type="simple"')) ?></a></span>
        <span class="j-togglers hide"><a href="#" class="link-ajax j-toggler"
                                         data-type="ajax"><span><?= _t('item-form', 'Удобная форма загрузки фотографий') ?></span></a></span>
      </div>

    </div>
  </div>

  <div class="row">
    <div class="col-sm-9 col-sm-offset-3">
      <h2 class="l-pageSubheading"><?= _t('item-form', 'Местоположение') ?></h2>
    </div>
  </div>

  <div class="j-geo">

    <div class="form-group">
      <label class="col-sm-3 control-label"><?= _t('item-form', 'Город') ?><span class="required-mark">*</span></label>
      <div class="col-md-3 col-sm-6">
        <?= Geo::i()->citySelect($city_id, true, 'city_id', array(
          'on_change' => 'jForm.onCitySelect',
          'country_on_change'=>'jForm.onCountrySelect',
          'form' => 'bbs-form',
          'required' => true,
        )); ?>
        <span class="j-regions-delivery <?php if (!$cat_id || empty($cat_data['regions_delivery'])) { ?>hide<?php } ?>">
            <label class="checkbox inline">
              <input type="checkbox" name="regions_delivery"
                     class="j-regions-delivery-checkbox" <?php if (!empty($regions_delivery)) { ?> checked="checked"<?php } ?>> <small><?= _t('item-form', 'Доставка в регионы') ?></small>
            </label>
          </span>
      </div>
    </div>

    <?php if ($districtsEnabled) { ?>
      <div class="form-group j-geo-district<?= !$districtsVisible ? ' hide' : '' ?>">
        <label class="col-sm-3 control-label"><?= _t('item-form', 'Район') ?></label>
        <div class="col-md-3 col-sm-6">
          <select name="district_id" autocomplete="off"
                  class="form-control"><?= Geo::districtOptions($city_id, $district_id, _t('item-form', 'Не указан')) ?></select>
        </div>
      </div>
    <?php } ?>

    <div class="form-group j-geo-metro"<?php if (!$city_id || !$city_data['metro']) { ?> style="display: none;"<?php } ?>>
      <input type="hidden" name="metro_id" value="<?= $metro_id ?>" class="j-geo-metro-value"/>
      <label class="col-sm-3 control-label"><?= _t('item-form', 'Метро') ?></label>
      <div class="col-md-6 col-sm-9">
        <div class="dropdown form-group-noinput">
          <div class="j-geo-metro-link-empty"<?php if ($metro_id) { ?> style="display: none;"<?php } ?>>
            <a href="#"
               class="link-ajax ajax-ico j-geo-metro-link"><span><?= _t('item-form', 'Выберите станцию') ?></span> <i
                class="fa fa-chevron-down"></i></a>
          </div>
          <div class="j-geo-metro-link-selected"<?php if (!$metro_id) { ?> style="display: none;"<?php } ?>>
            <div class="c-metro-selected">
              <span class="c-metro-ico j-color"
                    style="background-color:<?= ($metro_id ? $metro_data['sel']['branch']['color'] : '') ?>;"></span>
              <a href="#"
                 class="j-geo-metro-link j-title"><?= ($metro_id ? $metro_data['sel']['branch']['t'] . ' &raquo; ' . $metro_data['sel']['station']['t'] : '') ?></a>
              <a href="javascript:void(0);" class="link-ico link-red mrgl5 j-geo-metro-cancel"<? if (!$metro_id) { ?> style="display: none;"<? } ?>><i class="fa fa-times"></i></a>
            </div>
          </div>
          <div class="dropdown-menu j-geo-metro-popup">
            <div class="j-step1"></div>
            <div class="j-step2" style="display: none;"></div>
          </div>
        </div>
      </div>
    </div>

    <div id="j-i-geo-addr"<?php if (!$cat_id || !$cat_data['addr']) { ?> style="display:none;"<?php } ?>>
      <div class="form-group">
        <label class="col-sm-3 control-label"><?= _t('item-form', 'Адрес объекта') ?></label>
        <div class="col-md-6 col-sm-9">
          <input type="hidden" name="addr_lat" id="j-i-geo-addr-lat" value="<?= $addr_lat ?>"/>
          <input type="hidden" name="addr_lon" id="j-i-geo-addr-lon" value="<?= $addr_lon ?>"/>
          <input type="text" name="addr_addr" class="form-control" id="j-i-geo-addr-addr" value="<?= $addr_addr ?>"/>
          <div class="help-block mrgb0"><?= _t('item-form', 'Укажите улицу, район, номер дома и т.п.') ?></div>
        </div>
      </div>
      <div class="form-group">
        <div class="col-md-6 col-sm-9 col-sm-offset-3">
          <div id="j-i-geo-addr-map" style="height: 250px; width: 100%;"></div>
        </div>
      </div>
    </div>

  </div><!-- /.j-geo -->

  <div class="row">
    <div class="col-sm-9 col-sm-offset-3 j-i-contacts-block">
      <h2 class="l-pageSubheading"><?= _t('item-form', 'Ваши контактные данные') ?></h2>
      <?php if ($contactsFromProfile) { ?>
        <div class="alert alert-info">
          <?php if ($edit) { ?>
            <?= _t('item-form', 'Ваши контактные данные для объявления берутся из <a [link_settings]>настроек</a> в личном кабинете.', array(
              'link_settings' => 'href="' . Users::url('my.settings') . '" target="_blank"'
            )) ?>
          <?php } else { ?>
            <?= _t('item-form', 'Изменить ваши контактные данные вы можете в личном кабинете в разделе <a [link_settings]>настройки</a>.', array(
              'link_settings' => 'href="' . Users::url('my.settings') . '" target="_blank"'
            )) ?>
          <?php } ?>
        </div>
      <?php } # $contactsFromProfile ?>
    </div>
  </div>

  <div class="form-group"
       id="j-i-name-block"<?php if (($shop && $publisher_only_shop) || ($contactsFromProfile && $edit)) { ?> style="display: none;"<?php } ?>>
    <label class="col-sm-3 control-label"><?= _t('item-form', 'Контактное лицо') ?><span class="required-mark">*</span></label>
    <div class="col-md-6 col-sm-9">
      <input type="text" name="name" class="form-control<?php if (!$contactsFromProfile) { ?> j-required<?php } ?>"
             value="<?= $name ?>"<?php if ($contactsFromProfile) { ?> readonly="readonly"<?php } ?> placeholder=""
             id="j-i-name" maxlength="50"/>
      <div class="help-block mrgb0"><?= _t('item-form', 'Имя появится в блоке с контактной информацией') ?></div>
    </div>
  </div>

  <div class="form-group">
    <div class="col-sm-9 col-sm-offset-3">
      <div class="j-cat-owner">
        <?= ($cat_id ? $cat_data['owner'] : '') ?>
      </div>
    </div>
  </div>

  <?php if (Users::registerPhone() && !$edit) { ?>
    <div class="form-group">
      <label class="col-sm-3 control-label"><?= _t('item-form', 'Номер телефона') ?><span class="required-mark">*</span></label>
      <div class="col-md-3 col-sm-6">
        <?php if (empty($phone_number) || !$phone_number_verified) { ?>
          <?= $this->users()->registerPhoneInput(array('name' => 'phone', 'value' => (!empty($phone_number) ? '+' . $phone_number : '')), array('item-form' => true)) ?>
        <?php } else { ?>
          <div class="input-group">
            <input type="text" class="form-control" value="+<?= HTML::escape($phone_number) ?>" disabled="disabled"/>
            <span class="input-group-addon">
            <i class="fa fa-check text-success hidden-xs"></i>
          </span>
          </div>
        <?php } ?>
      </div>
    </div>
  <?php } ?>

  <div class="form-group<?php if ($edit) { ?> hide<?php } ?>">
    <label class="col-sm-3 control-label"><?= _t('item-form', 'E-mail адрес') ?><span
        class="required-mark">*</span></label>
    <div class="col-md-3 col-sm-6">
      <input type="email" name="email"
             value="<?= (!empty($email) ? HTML::escape($email) : '') ?>" <?php if ($userID) { ?> readonly="readonly"<?php } ?>
             class="form-control j-required" maxlength="100" autocorrect="off" autocapitalize="off"/>
    </div>
  </div>

  <div class="form-group<?php if ($contactsFromProfile && $edit) { ?> hide<?php } ?>">
    <label class="col-sm-3 control-label"><?= _t('item-form', 'Контакты') ?></label>
    <div class="col-md-3 col-sm-6">

      <div class="form-group-item" id="j-i-phones"></div>

      <?php foreach (Users::contactsFields() as $contact): ?>
        <div class="form-group-item">
          <div class="input-group">
            <span class="input-group-addon"><i class="<?= $contact['icon'] ?>"></i></span>
            <input type="text" name="contacts[<?= $contact['key'] ?>]"
                   value="<?= isset($contacts[$contact['key']]) ? HTML::escape($contacts[$contact['key']]) : '' ?>"
                   class="form-control j-c-<?= $contact['key'] ?>" <?= $contactsFromProfile ? 'readonly="readonly"' : '' ?>
                   placeholder="<?= $contact['title'] ?>" maxlength="<?= $contact['maxlength'] ?>">
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

  <?php if ($servicesAvailable) { ?>
    <div class="j-svc-block" style="display: none;">
      <div class="row">
        <div class="col-sm-9 col-sm-offset-3">
          <h2 class="l-pageSubheading"><?= _t('item-form', 'Продвижение объявления') ?></h2>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-9 col-sm-offset-3">
          <div class="l-svc">
            <?php foreach ($svc_data as $v) {
              if (empty($v['add_form'])) continue;
              ?>
              <div class="l-svc-item j-svc-item j-svc-<?= $v['id'] ?>">
                <label>
                  <span class="l-svc-item-top">
                    <span class="l-svc-item-price"><b class="j-price"><?= tpl::currency($v['price']) ?></b> <?= $curr ?></span>
                    <input type="radio" name="svc" value="<?= $v['id'] ?>"/>
                    <span class="l-svc-item-icon"><img src="<?= $v['icon_s'] ?>" alt=""/></span>
                    <span class="l-svc-item-title"><?= $v['title_view'] ?></span>
                  </span>
                </label>
                <div class="l-svc-item-descr j-svc-descr">
                  <?php if ($v['id'] == BBS::SERVICE_FIX && !empty($v['period_type']) && $v['period_type'] == BBS::SVC_FIX_PER_DAY) { ?>
                    <div class="mrgb10">
                      <?= _t('bbs', 'Закрепить на [input] день', array('input' =>
                        '<input class="input-mini text-center" value="' . config::sysAdmin('bbs.services.fix.days.default', 1, TYPE_UINT) . '" type="number" name="fix_days" min="1" max="999" />'
                      )) ?>
                    </div>
                  <?php } ?>
                  <?= nl2br($v['description']); ?>
                </div>
              </div>
            <?php } ?>
            <div class="l-svc-item active j-svc-item j-svc-0">
              <label>
                <span class="l-svc-item-top">
                  <span class="l-svc-item-price"></span>
                  <input type="radio" name="svc" value="0" checked="checked"/>
                  <span class="l-svc-item-icon">
                    <img src="<?= bff::url('/img/square-grey.png') ?>" alt=""/>
                  </span>
                  <span class="l-svc-item-title"><?= _t('item-form', 'Бесплатное объявление') ?></span>
                </span>
              </label>
              <div class="l-svc-item-descr j-svc-descr">
                <?= _t('item-form', 'Бесплатное объявление, ничем не выделено на фоне таких же предложений') ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php } # $servicesAvailable ?>
  <?php if ($agreementAvailable) { ?>
    <div class="form-group">
      <div class="col-sm-9 col-sm-offset-3">
        <div class="checkbox">
          <label>
            <input name="agree" type="checkbox"
                   class="j-required"/><?= _t('item-form', 'Я соглашаюсь с <a [link_agreement]>правилами использования сервиса</a>, а также с передачей и обработкой моих данных в [site_title]. Я подтверждаю своё совершеннолетие и ответственность за размещение объявления', array('link_agreement' => 'href="' . Users::url('agreement') . '" target="_blank"', 'site_title' => Site::title('bbs.item.form.agreement'))) ?>
            <span class="required-mark">*</span>
          </label>
        </div>
      </div>
    </div>
  <?php } ?>

  <div class="form-group">
    <div class="col-sm-9 col-sm-offset-3">
      <input type="submit" class="btn btn-success j-submit"
             value="<?= ($edit ? _t('item-form', 'Изменить') : _t('item-form', 'Опубликовать объявление')) ?>"
             data-loading-text="<?= _te('item-form', 'Подождите...') ?>"/>
      <?php if ($edit) { ?>
        <span class="btn btn-default j-cancel"><?= _t('', 'Отмена') ?></span>
      <?php } ?>
    </div>
  </div>

  <script type="text/html" class="j-tpl-phones">
    <div class="form-group-item">
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-phone"></i></span>
        <input type="tel" maxlength="30" name="phones[<%= index %>]" value="<%= value %>" class="form-control j-phone"
        <% if(o.contactsFromProfile) { %> readonly="readonly" <% } %> placeholder="<%= o.lang.phones_tip %>" />
      </div>
      <% if(plus) { %>
      <div class="mrgt5"><a class="link-ajax j-plus" href="#"><span><%= o.lang.phones_plus %></span></a></div>
      <% } %>
    </div>
  </script>

</form>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function () {
    <?php
    $jsSettings = array(
      # item
      'itemID' => $id,
      'edit' => $edit,
      # category
      'catsRootID' => BBS::CATS_ROOTID,
      'catsMain' => $this->catsList('form', 'init'),
      'catTypesEx' => (BBS::CATS_TYPES_EX ? true : false),
      'catTypeSeek' => BBS::TYPE_SEEK,
      'catEditable' => BBS::categoryFormEditable(),
      # images
      'imgLimit' => $imagesLimit,
      'imgMaxSize' => $img->getMaxSize(),
      'imgUploaded' => $imagesUploaded,
      'imgData' => $images,
      'imgClasses' => array(
          'active'  => 'j-img-active',
          'first'   => 'ad-gallery-first',
          'preview' => 'ad-gallery-item-img',
      ),
      # geo
      'geoCityID' => $city_id,
      # user
      'phonesLimit' => Users::i()->profilePhonesLimit,
      'phonesData' => $phones,
      'contactsFromProfile' => $contactsFromProfile,
      'owner_types' => array('private' => BBS::OWNER_PRIVATE, 'business' => BBS::OWNER_BUSINESS),
      # lang
      'lang' => array(
        'maxlength_symbols_left' => _t('', '[symbols] осталось'),
        'maxlength_symbols' => _t('', 'знак;знака;знаков'),
        'upload_typeError' => _t('item-form', 'Допустимы только следующие типы файлов: {extensions}'),
        'upload_sizeError' => _t('item-form', '"Файл {file} слишком большой, максимально допустимый размер {sizeLimit}'),
        'upload_minSizeError' => _t('item-form', 'Файл {file} имеет некорректный размер'),
        'upload_emptyError' => _t('item-form', 'Файл {file} имеет некорректный размер'),
        'upload_limitError' => _t('item-form', 'Вы можете загрузить не более {limit} изображений'),
        'upload_onLeave' => _t('item-form', 'Происходит загрузка изображения, если вы покинете эту страницу, загрузка будет прекращена'),
        'email_wrong' => _t('users', 'E-mail адрес указан некорректно'),
        'phones_tip' => _t('item-form', 'Номер телефона'),
        'phones_plus' => _t('item-form', '+ ещё<span [attr]> телефон</span>', array('attr' => 'class="hidden-xs"')),
        'phones_req' => _t('item-form', 'Укажите номер телефона'),
        'price' => _t('item-form', 'Укажите цену'),
      ),
      'autoTitle' => $autoTitle,
      'catLastTitle' => ! empty($cat_path) ? end($cat_path) : '',
      'catPath' => $cat_path,
    );
    if ($city_id) {
      $cityData = Geo::model()->regionData(array('id' => $city_id), true);
      if ( ! empty($cityData['declension'][LNG])) {
        $jsSettings['geoCityDeclension'] = $cityData['declension'][LNG];
      }
    }
    # при добавлении с возможностью выбора типа "частное лицо/магазина" - формируем данные о контактах
    if (!$edit && $publisher == BBS::PUBLISHER_USER_OR_SHOP && $shop && $shop_data) {
      $jsSettings['contacts_shop'] = &$shop_data;
      $jsSettings['contacts_shop_phones'] = &$shop_data['phones'];
      $jsSettings['contacts_user'] = $contacts;
      $jsSettings['contacts_user_phones'] = $phones;
      foreach ($shop_data as $k => &$v) {
        if (!isset($aData[$k])) continue;
        $jsSettings['contacts_user'][$k] = &$aData[$k];
        if ($k == 'city_data') {
          $v = array('title' => $v['title'], 'metro' => !empty($v['metro']), 'pid' => $v['pid'], 'declension'=>$v['declension']);
          $aData[$k] = array('title' => $aData[$k]['title'], 'metro' => !empty($aData[$k]['metro']), 'pid' => $aData[$k]['pid'], 'declension'=>$aData[$k]['declension']);
        }
      }
      unset($v);
    }
    if ($publicationPeriod) {
      foreach ($publicationPeriodOpts as & $v) {
        $v = _t('item-form', 'до [date]', array('date' => tpl::date_format2(time() + $v['days'] * 86400, false, true)));
      }
      unset($v);
      $jsSettings['periods'] = $publicationPeriodOpts;
    }
    ?>
    jForm.init(<?= func::php2js($jsSettings) ?>);
  });
  <?php js::stop(); ?>
</script>
