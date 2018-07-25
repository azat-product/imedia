<?php
$form = empty($noForm);
if( ! isset($prefix)) $prefix = '';
$formname = function($name) use($prefix){
  return $prefix ? $prefix.'['.$name.']' : $name;
};
if( ! isset($on)) $on = 0;
if( ! isset($p)) $p = 0;
if( ! isset($t)) $t = BBS::SVC_UP_AUTO_SPECIFIED;
if( ! isset($h)) $h = 0;
if( ! isset($m)) $m = 0;
if( ! isset($fr_h)) $fr_h = 0;
if( ! isset($fr_m)) $fr_m = 0;
if( ! isset($to_h)) $to_h = 0;
if( ! isset($to_m)) $to_m = 0;
if( ! isset($int)) $int = 0;
$hours = range(0, 23);
foreach($hours as & $v){
  $s = (string)$v;
  $s = strlen($s) == 1 ? '0'.$s : $s;
  $v = array('id' => $v, 't' => $s);
}unset($v);

$minutes = range(0, 50, 10);
foreach($minutes as & $v){
  $s = (string)$v;
  $s = strlen($s) == 1 ? '0'.$s : $s;
  $v = array('id' => $v, 't' => $s);
}unset($v);

$intervals = array(
  '30' => array('id' => 30, 't' => _t('bbs', 'пол часа')),
  '60' => array('id' => 60, 't' => _t('bbs', 'час')),
  '120' => array('id' => 120, 't' => _t('bbs', 'два часа')),
  '180' => array('id' => 180, 't' => _t('bbs', 'три часа')),
  '240' => array('id' => 240, 't' => _t('bbs', 'четыре часа')),
  '300' => array('id' => 300, 't' => _t('bbs', 'пять часов')),
  );
  ?>
  <?php if($form): ?>
  
  <div class="sr-list-item-autoup-container j-i-up-auto-sett">
    <form class="form-horizontal" method="post" action="">
      <input type="hidden" name="id" value="<?= $id ?>" />
  <?php endif; ?>
      <div class="checkbox pdt0">
        <label>
          <input type="checkbox" name="<?= $formname('on') ?>" <?= $on ? 'checked="checked"' : '' ?> value="1" />
          <?= _t('bbs', 'Поднимать автоматически') ?>
        </label>
      </div>
      <div class="sr-list-item-autoup-selects">
        <select class="form-control input-inline"> name="<?= $formname('p') ?>"><?= HTML::selectOptions(BBS::svcUpAutoPeriods(), $p, false, 'id', 't') ?></select>
        <select class="form-control input-inline" name="<?= $formname('t') ?>">
          <option value="<?= BBS::SVC_UP_AUTO_SPECIFIED ?>" <?= $t == BBS::SVC_UP_AUTO_SPECIFIED ? 'selected' : ''?>><?= _t('', 'в') ?></option>
          <option value="<?= BBS::SVC_UP_AUTO_INTERVAL ?>" <?= $t == BBS::SVC_UP_AUTO_INTERVAL ? 'selected' : ''?>><?= _t('bbs', 'переодически') ?></option>
        </select>
        <span class="sr-list-item-autoup-selects-time j-a-up-type j-a-up-type-<?= BBS::SVC_UP_AUTO_SPECIFIED ?> <?= $t != BBS::SVC_UP_AUTO_SPECIFIED ? 'hide' : ''?>">
          <select class="form-control input-inline" name="<?= $formname('h') ?>"><?= HTML::selectOptions($hours, $h, false, 'id', 't') ?></select>
          :
          <select class="form-control input-inline" name="<?= $formname('m') ?>"><?= HTML::selectOptions($minutes, $m, false, 'id', 't') ?></select>
        </span>
      </div>
      <div class="sr-list-item-autoup-selects j-a-up-type j-a-up-type-<?= BBS::SVC_UP_AUTO_INTERVAL ?> <?= $t != BBS::SVC_UP_AUTO_INTERVAL ? 'hide' : ''?>">
        <?= _t('', 'c') ?>
        <select class="form-control input-inline" name="<?= $formname('fr_h') ?>"><?= HTML::selectOptions($hours, $fr_h, false, 'id', 't') ?></select>
        :
        <select class="form-control input-inline" name="<?= $formname('fr_m') ?>"><?= HTML::selectOptions($minutes, $fr_m, false, 'id', 't') ?></select>
        <?= _t('', 'до') ?>
        <select class="form-control input-inline" name="<?= $formname('to_h') ?>"><?= HTML::selectOptions($hours, $to_h, false, 'id', 't') ?></select>
        :
        <select class="form-control input-inline" name="<?= $formname('to_m') ?>"><?= HTML::selectOptions($minutes, $to_m, false, 'id', 't') ?></select>
        <?= _t('', 'через') ?>
        <select class="form-control input-inline" name="<?= $formname('int') ?>"><?= HTML::selectOptions($intervals, $int, false, 'id', 't') ?></select>
      </div>
      <div class="help-block extrasmall">
        <?= _t('bbs', 'При активации данной услуги, деньги с вашего счета списываются при каждом автоподнятии.') ?>
      </div>
      <?php if($form): ?>
      <div>
        <input type="submit" class="btn btn-success btn-small j-submit" data-loading-text="<?= _te('item-form', 'Подождите...') ?>" value="<?= _te('', 'Применить'); ?>" />
        <a class="btn btn-default btn-small j-cancel"><?= _t('', 'Отмена'); ?></a>
      </div>
    </form>
  </div>

  <?php else: ?>
  <div class="l-spacer"></div>
<?php endif; ?>