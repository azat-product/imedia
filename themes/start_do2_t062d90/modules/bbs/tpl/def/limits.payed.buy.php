<?php
/**
 * Платные лимиты: форма выбора тарифа и способа оплаты
 * @var $this BBS
 * @var $ps array способы оплаты
 * @var $ps_active_key string ключ выбранного способа оплаты по-умолчанию
 * @var $user_balance float текущий баланс пользователя
 * @var $extend boolean продление действующего лимита
 * @var $single array
 * @var $settings array доступные варианты для оплаты
 */

tpl::includeJS('bbs.limits.payed', false);
$price = 0;
?>

<div class="l-pageHeading">

  <?php if($extend) { ?>
    <h1 class="l-pageHeading-title"><?= _t('bbs', 'Продлить лимит объявлений'); ?></h1>
    <div class="mrgt10"><?= _t('bbs', 'Срок действия услуги - [term]. Будет продлена до [date].', array(
      'term' => tpl::declension($term, _t('', 'день;дня;дней')),
      'date' => tpl::date_format2($expire),
      )) ?></div>
    <?php } else { ?>
    <h1 class="l-pageHeading-title"><?= _t('bbs', 'Увеличить лимит объявлений'); ?></h1>
    <div class="mrgt10"><?= _t('bbs', 'Вы превысили лимит активных объявлений в данной категории. Чтобы увеличить лимит, выберите пакет дополнительных объявлений.'); ?>
      <?= $term ? _t('bbs', 'Срок действия услуги - [term].', array('term' => tpl::declension($term, _t('', 'день;дня;дней')))) : '' ?></div>
  <?php } ?>

</div>


<div class="i-services">
  <form class="form-horizontal" action="" id="j-limits-paid-form">
    <input type="hidden" name="ps" value="<?= $ps_active_key ?>" class="j-ps-value" />
    <input type="hidden" name="items" value="1" />

  <div class="i-formpage__packages">

    <?php if( ! empty($single)): $price = $single['price']; ?>
    <div class="usr-package active j-package">
      <label class="usr-packag-label">
        <input type="radio" name="single" value="1" data-price="<?= $single['price'] ?>" checked="checked" />
      </label>
      <div class="usr-package-text">
        <?= _t('bbs', 'Дополнительное объявление в рубрике [title]', array('title' => '<div class="usr-package-text-title">'.$title.'</div>')); ?>
      </div>
      <div class="usr-package-price">
        <strong><?= $single['price'] ?> <?= Site::currencyDefault() ?></strong>
      </div>
    </div>
    <?php endif; ?>

    <?php if( ! empty($settings)): ?>
    <div class="usr-package j-package">
      <label class="usr-packag-label">
        <input type="radio"  name="single" value="0" <?= empty($single) ? 'checked="checked"' : '' ?> />
      </label>
      <div class="usr-package-text">
        <?= $extend ? _t('bbs', 'Продлить пакет платных объявлений в рубрике <strong>[title]</strong>', array('title' => $title))
        : _t('bbs', 'Купить пакет платных объявлений в рубрике <strong>[title]</strong>', array('title' => $title)); ?>
      </div>
      <div class="usr-package-select">
        <?php if($extend): $fst = reset($settings); ?>
        <?= tpl::declension($fst['items'], _t('bbs', 'объявление;объявления;объявлений')) ?>
        <span class="hidden">
        <?php endif; ?>
        <select class="form-control j-items">
          <?php $fst = false; foreach($settings as $v): if( ! $fst){ $fst = $v; if(empty($single)) { $price = $v['price']; } } ?>
          <option value="<?= $v['items'] ?>" data-price="<?= $v['price'] ?>" ><?= tpl::declension($v['items'], _t('bbs', 'объявление;объявления;объявлений')) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if($extend):?></span><?php endif; ?>
      </div>
      <div class="usr-package-price">
        <strong><span class="j-price"><?= $fst['price'] ?></span> <?= Site::currencyDefault() ?></strong>
      </div>
    </div>
  <?php endif; ?>

  </div>

  <div class="j-ps-block">
    <h2 class="l-pageSubheading"><?= _t('bbs', 'Выберите способ оплаты') ?></h2>
    <div class="l-payMethods_inline">
      <?php foreach($ps as $key=>$v) { ?>
        <div class="l-payMethods-item<?php if($v['active']) { ?> active<?php } ?> j-ps-item" data-key="<?= $key ?>">
          <div class="l-payMethods-item-in">
            <div class="l-payMethods-item-logo">
              <img src="<?= $v['logo_desktop'] ?>" alt="" />
            </div>
            <div class="l-payMethods-item-content">
              <label class="radio">
                <input type="radio" name="ps" autocomplete="off" value="<?= $key ?>"<?php if($v['active']) { ?> checked="checked" <?php } ?> class="l-payMethods-item-radio j-radio" />&nbsp;<?= $v['title'] ?>
              </label>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
  
  <h2 class="l-pageSubheading"><?= _t('', 'Всего к оплате:'); ?> <b class="j-total"><?= $price ?></b> <?= Site::currencyDefault() ?></h2>
  
  <input type="submit" class="btn btn-success j-submit" value="<?= _te('', 'Оплатить'); ?>" />
  <button class="btn btn-default" onclick="history.back(); return false;"><?= _t('', 'Отмена'); ?></button>

</form>
<div id="j-limits-paid-form-request" style="display: none;"></div>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBBSLimitsPayed.init(<?= func::php2js(array(
      'lang' => array(
        'svc_select' => _t('bbs', 'Выберите услугу'),
        'ps_select' => _t('bbs', 'Выберите способ оплаты'),
        ),
      'user_balance' => $user_balance,
      )) ?>);
  });
  <?php js::stop(); ?>
</script>