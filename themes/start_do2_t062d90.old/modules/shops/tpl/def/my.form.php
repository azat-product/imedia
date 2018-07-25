<?php
/**
 * Форма магазина: открытие / редактирование настроек магазина
 * @var $this Shops
 * @var $id integer ID магазина или 0 (открытие)
 * @var $is_open boolean выполняется открытие
 * @var $is_edit boolean выполняется редактирование
 * @var $url_submit string URL обработки формы
 * @var $open_text string текст инструкции открытия магазина
 * @var $abonements string HTML форма тарифов услуги абонемент
 * @var $aData array данные о магазине
 */
Geo::mapsAPI(true);
tpl::includeJS(array('qquploader'), true);
tpl::includeJS(array('shops.form'), false, 2);
$aData = HTML::escape($aData, 'html', array('title', 'descr', 'site', 'addr_addr'));
$show_titles = $is_open;
?>

<?php if ($is_open) { ?>
  <div class="mrgb15">
    <?= $open_text ?>
  </div>
<?php } else { ?>
  <?php if ($status == Shops::STATUS_BLOCKED) { ?>
    <div class="alert alert-danger">
      <?= _t('shops', 'Ваш магазин был заблокирован модератором, причина:<br /><strong>[reason]</strong>',
        array('reason' => $blocked_reason)) ?>
    </div>
  <?php } ?>
<?php } ?>
<form class="form-horizontal" action="" id="j-shops-form">
  <?= isset($abonements) ? $abonements : '' ?>
  <?php if ($show_titles) { ?>
  <div>
    <div class="row">
      <div class="col-sm-9 col-sm-offset-3">
        <h2 class="l-pageSubheading"><?= _t('shops', 'Общая информация') ?></h2>
      </div>
    </div>
    <?php } ?>
      <div class="relative">
          <? if($titlesLang && count($languages) > 1): ?>
              <div class="form-lang" <?= $is_open ? 'style="top: 10px;"' : ''?>>
                  <? foreach ($languages as $k => $v): ?>
                      <a href="javascript:" class="j-lang <?= $k == LNG ? 'active' : '' ?>" data-lng="<?= $k ?>" data-country="<?= $v['country'] ?>"><span class="country-icon country-icon-<?= $v['country'] ?>"></span></a>
                  <? endforeach; ?>
              </div>
          <? endif; ?>
    <div class="form-group">
      <label class="col-sm-3 control-label"><?= _t('shops', 'Логотип') ?><br/>
        <small><?= _t('shops', 'Магазины с логотипом пользуются большим доверием') ?></small>
      </label>
      <div class="col-sm-9">
        <div class="usr-settings-photo">
          <?php if ($is_open) { ?><input type="hidden" name="logo" value="" id="j-shop-logo-fn"/><?php } ?>
          <img alt="" src="<?= $logo_preview ?>" id="j-shop-logo-preview"/>
        </div>
        <div class="usr-settings-photo-upload">
          <a href="javascript:void(0);" class="btn btn-default"
             id="j-shop-logo-upload"><?= _t('shops', 'Загрузить логотип') ?></a>
          <a href="#" id="j-shop-logo-delete"
             class="link-ajax link-red mrgl10 remove<?php if (!$logo) { ?> hide<?php } ?>"><i class="fa fa-times"></i>
            <span><?= _t('shops', 'удалить') ?></span></a>
        </div>
        <div
          class="help-block"><?= _t('shops', 'Максимальный размер файла - [size]', array('size' => $logo_maxsize_format)) ?></div>
      </div>
    </div>

    <div class="form-group">
      <label for="sh_title-<?= LNG ?>" class="col-sm-3 control-label"><?= _t('shops', 'Название') ?><span
          class="required-mark">*</span></label>
      <div class="col-md-6 col-sm-9">
          <? if ($titlesLang): ?>
              <? foreach ($languages as $k => $v): ?>
                  <input type="text" name="title[<?= $k ?>]" value="<?= isset($title[$k]) ? $title[$k] : '' ?>" class="form-control lang-field j-lang-form j-lang-form-<?= $k ?> <?= $k != LNG ? 'hide' : '' ?>" maxlength="50" id="sh_title-<?= $k ?>"/>
              <? endforeach; ?>
          <? else: ?>
              <input type="text" name="title" value="<?= $title ?>" class="form-control j-required" id="sh_title-<?= LNG ?>" maxlength="50" />
          <? endif; ?>
      </div>
    </div>

    <?php if ($cats_on) { ?>
      <div class="form-group" id="j-shop-cats">
        <label class="col-sm-3 control-label"><?= _t('shops', 'Категория') ?><span
            class="required-mark">*</span></label>
        <div class="col-md-6 col-sm-9 form-group-noinput">
          <input type="hidden" name="category-last" value="0" class="j-cat-selected-last j-required"/>
          <div class="dropdown">
            <a href="#" class="link-ajax j-cat-select-link"><span><?= _t('shops', 'Выберите категорию') ?></span> <i
                class="fa fa-chevron-down"></i></a>
            <div class="dropdown-menu j-cat-select-popup">
              <div class="i-formpage__catselect__popup__content">
                <div class="j-cat-select-step1-desktop">
                  <?= $this->catsList('form', bff::DEVICE_DESKTOP, 0); ?>
                </div>
                <div class="j-cat-select-step2-desktop hide"></div>
              </div>
            </div>
          </div>
          <div class="j-cat-selected-items"></div>
        </div>
      </div>
    <?php } ?>

    <div class="form-group">
      <label for="sh_descr-<?= LNG ?>" class="col-sm-3 control-label"><?= _t('shops', 'Описание') ?><span
          class="required-mark">*</span></label>
      <div class="col-md-6 col-sm-9">
          <? if ($titlesLang): ?>
              <? foreach ($languages as $k => $v): ?>
                  <textarea name="descr[<?= $k ?>]" class="form-control lang-field j-lang-form j-lang-form-<?= $k ?> <?= $k != LNG ? 'hide' : '' ?>" rows="6" autocapitalize="off" id="sh_descr-<?= $k ?>"><?= isset($descr[$k]) ? $descr[$k] : '' ?></textarea>
              <? endforeach; ?>
          <? else: ?>
              <textarea name="descr" class="form-control j-required" id="sh_descr-<?= LNG ?>" rows="6" autocapitalize="off"><?= $descr ?></textarea>
          <? endif; ?>
      </div>
    </div>

    <div id="j-shop-geo">
      <div class="form-group">
        <label class="col-sm-3 control-label"><?= _t('shops', 'Город') ?></label>
        <div class="col-md-6 col-sm-3">
          <?= Geo::i()->citySelect($region_id, true, 'region_id', array(
            'on_change' => 'jShopsForm.onCitySelect',
            'form' => 'shops-' . ($is_edit ? 'settings' : 'form'),
          )); ?>
        </div>
      </div>
      <div id="j-shop-geo-addr">
        <div class="form-group">
          <label for="shop-geo-addr-addr-<?= LNG ?>" class="col-sm-3 control-label"><?= _t('shops', 'Адрес магазина') ?></label>
          <div class="col-md-6 col-sm-9">
            <input type="hidden" name="addr_lat" id="j-shop-geo-addr-lat" value="<?= $addr_lat ?>"/>
            <input type="hidden" name="addr_lon" id="j-shop-geo-addr-lon" value="<?= $addr_lon ?>"/>
              <? if ($titlesLang): ?>
                  <? foreach ($languages as $k => $v): ?>
                      <input type="text" name="addr_addr[<?= $k ?>]" value="<?= isset($addr_addr[$k]) ? $addr_addr[$k] : '' ?>" class="form-control j-shop-geo-addr-addr lang-field j-lang-form j-lang-form-<?= $k ?> <?= $k != LNG ? 'hide' : '' ?>" id="shop-geo-addr-addr-<?= $k ?>"/>
                  <? endforeach; ?>
              <? else: ?>
                  <input type="text" name="addr_addr" id="shop-geo-addr-addr-<?= LNG ?>" value="<?= $addr_addr ?>" class="form-control j-shop-geo-addr-addr" />
              <? endif; ?>
          </div>
        </div>
        <div class="form-group">
          <div class="col-md-6 col-sm-9 col-sm-offset-3">
            <div id="j-shop-geo-addr-map" style="height: 250px; width: 100%;"></div>
          </div>
        </div>
      </div>
    </div>
      </div>

    <?php if ($show_titles) { ?>

      <div class="row">
        <div class="col-sm-9 col-sm-offset-3">
          <h2 class="l-pageSubheading"><?= _t('shops', 'Контактные данные') ?></h2>
        </div>
      </div>

    <?php } ?>

    <div class="form-group">
      <label class="col-sm-3 control-label"><?= _t('shops', 'Контакты') ?></label>
      <div class="col-md-3 col-sm-6">
        <div class="form-group-item" id="j-shop-phones"></div>
        <?php foreach (Users::contactsFields() as $contact_key => $contact): ?>
          <div class="form-group-item">
            <div class="input-group">
              <span class="input-group-addon"><i class="<?= $contact['icon'] ?>"></i></span>
              <input type="text" name="contacts[<?= $contact_key ?>]"
                     value="<?= isset($contacts[$contact_key]) ? HTML::escape($contacts[$contact_key]) : '' ?>"
                     class="form-control" placeholder="<?= $contact['title'] ?>"
                     maxlength="<?= $contact['maxlength'] ?>">
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-group">
      <label for="sh_website" class="col-sm-3 control-label"><?= _t('shops', 'Ссылка на сайт') ?></label>
      <div class="col-md-3 col-sm-6">
        <div class="input-group">
          <span class="input-group-addon"><i class="fa fa-globe"></i></span>
          <input type="text" name="site" value="<?= $site ?>" id="sh_website" class="form-control"
                 placeholder="www.yoursite.com" maxlength="200"/>
        </div>
      </div>
    </div>

    <div class="form-group sh-social-networks">
      <label class="col-sm-3 control-label"><?= _t('shops', 'Социальные сети') ?></label>
      <div class="col-md-6 col-sm-9">
        <div id="j-shop-social-links"></div>
        <select id="j-shop-social-links-types" class="hide"><?= Shops::socialLinksTypes(true) ?></select>
        <div class="mrgt5">
          &nbsp;<a class="link-ajax" id="j-social-links-plus"
                   href="#"><span>+ <?= _t('shops', 'ещё социальная сеть') ?></span></a>
        </div>
      </div>
    </div>

    <div class="form-group">

      <?php $reset = !empty($default_price) ? reset($default_price) : array('ex' => '', 'm' => 0, 'pr' => 0); ?>
      <div class="col-sm-9 col-sm-offset-3">
        <input type="submit" class="btn btn-success j-submit"
               value="<?= ($is_edit ? _t('shops', 'Сохранить') : _t('shops', 'Открыть магазин')) ?>"/>
        <?php if ($is_open) { ?>
          <span class="btn btn-default" onclick="history.back();"><?= _t('', 'Отмена') ?></span>
        <?php } ?>
        <div
          class="help-inline j-abonement-help j-abonement-expire-block <?php if (!Shops::abonementEnabled() || !empty($user_abonement) || !$reset['m']) { ?>hide<?php } ?>">
          <?= _t('shops', 'тариф "[title]" до [expire]', array(
            'title' => '<span id="j-abonement-name">' . (!empty($default_name) ? $default_name : '') . '</span>',
            'expire' => '<span class="j-abonement-expire">' . $reset['ex'] . '</span>',
          )) ?><span
            class="j-abonement-price-block <?= !$reset['pr'] ? 'hide' : '' ?>">, <?= _t('shops', 'к оплате [price]', array(
              'price' => '<strong><span class="j-abonement-price">' . $reset['pr'] . '</span> ' . Site::currencyDefault() . '</strong>',
            )); ?></span>
        </div>
        <?php if ($is_edit && bff::servicesEnabled()) { ?><a
          href="<?= Shops::url('shop.promote', array('id' => $id, 'from' => 'settings')) ?>" class="btn btn-info"><i
            class="fa fa-arrow-up"></i> <span class="hidden-xs"><?= _t('shops', 'Продвинуть магазин') ?></span>
          </a><?php } ?>
        <?php if ($is_open) { ?><span class="btn btn-default cancel"
                                      onclick="history.back();"><?= _t('', 'Отмена') ?></span><?php } ?>
      </div>
    </div>

    <?php if ($show_titles) { ?></div><?php } ?>

  <script type="text/html" class="j-tpl-category-item">
    <div class="j-cat-selected-item">
      <div class="l-categories-selectedItem">
        <input type="hidden" name="cats[]" class="j-value" value="<%= id %>"/>
        <img src="<%= icon %>" alt=""/>
        <a href="javascript:void(0);"><%= title %></a> <a href="#" class="link-red j-cat-selected-item-delete"><i
            class="fa fa-times"></i></a>
      </div>
    </div>
  </script>

  <script type="text/html" class="j-tpl-shop-phones">
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

  <script type="text/html" class="j-tpl-social-links">
    <div class="l-socialForm form-group-item j-social-link">
      <div class="l-socialForm-select">
        <select name="social[<%= index %>][t]" class="form-control j-type"><%= types %></select>
      </div>
      <div class="l-socialForm-input">
        <div class="input-append sh-social-item">
          <input type="text" class="form-control" name="social[<%= index %>][v]" value="<%= value %>" maxlength="300"
                 placeholder="<%= o.lang.social_link %>"/>
          <a href="#" class="l-socialForm-delete link-red j-delete"><i class="fa fa-times"></i></a>
        </div>
      </div>
    </div>
  </script>

</form>


<script type="text/javascript">
  <?php js::start() ?>
  jShopsForm.init(<?= func::php2js(array(
    'edit' => $is_edit,
    'url_submit' => $url_submit,
    'lang' => array(
      'saved_success' => _t('', 'Настройки успешно сохранены'),
      'logo_upload_messages' => array(
        'typeError' => _t('shops', 'Допустимы только следующие типы файлов: {extensions}'),
        'sizeError' => _t('shops', 'Файл {file} слишком большой, максимально допустимый размер {sizeLimit}'),
        'minSizeError' => _t('shops', 'Файл {file} имеет некорректный размер'),
        'emptyError' => _t('shops', 'Файл {file} имеет некорректный размер'),
        'onLeave' => _t('shops', 'Происходит загрузка изоражения, если вы покинете эту страницу, загрузка будет прекращена'),
      ),
      'logo_upload' => _t('shops', 'Загрузка логотипа'),
      'category_select' => _t('shops', 'Выберите категорию магазина'),
      'social_link' => _t('shops', 'Ссылка'),
      'phones_tip' => _t('shops', 'Номер телефона'),
      'phones_plus' => _t('shops', '+ ещё<span [attr]> телефон</span>', array('attr' => 'class="hidden-xs"')),
    ),
    //logo
    'logoMaxSize' => $logo_maxsize,
    //cats
    'catsOn' => $cats_on,
    'catsMain' => $cats_main,
    'catsRootID' => Shops::CATS_ROOTID,
    'catsLimit' => Shops::categoriesLimit(),
    'catsSelected' => $cats,
    //phones
    'phonesLimit' => Shops::phonesLimit(),
    'phonesData' => $phones,
    //social links
    'socialLinksLimit' => Shops::socialLinksLimit(),
    'socialLinksData' => $social,
    'titlesLang'      => $titlesLang,
    'uploadProgress' => '<div class="align-center j-progress" style="width: 200px;  height: 80px;  float: left;  line-height: 80px;"> <img alt="" src="' . bff::url('/img/loading.gif') . '"> </div>',
  )) ?>);
  <?php js::stop() ?>
</script>