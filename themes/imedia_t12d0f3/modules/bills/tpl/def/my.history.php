<?php
/**
 * Кабинет: Счет - история счетов - layout
 * @var $this Bills
 * @var $balance integer|float текущая сумма на счете
 * @var $curr string валюты суммы на счете
 * @var $f array настройки фильтра
 * @var $list string список счетов (HTML)
 * @var $list_empty boolean список пустой
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 */
tpl::includeJS(array('history'), true);
tpl::includeJS(array('bills.my'), false);
?>
<div class="usr-bill-top">
  <div class="usr-bill-top-heading">
    <h2 class="usr-bill-top-title"><?= _t('bills', 'История операций') ?></h2>
  </div>
  <div class="usr-bill-top-r">
    <form class="form-inline" action="">
      <span><?= _t('bills', 'На вашем счету:') ?> <b><?= $balance.' '.$curr ?></b></span>
      <a href="<?= Bills::url('my.pay') ?>" class="btn btn-info"><?= _t('bills', 'Пополнить счет') ?></a>
    </form>
  </div>
</div>

<form action="" id="j-my-history-form">
  <input type="hidden" name="page" value="<?= $f['page'] ?>" />
  <input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-history-pp-value" />

  <!-- list -->
  <div class="usr-bill-list">
    <table class="table table-bordered table-hover">
      <tr>
        <th><?= _t('bills', 'Описание') ?></th>
        <th class="usr-bill-list-id"><?= _t('bills', '№ операции') ?></th>
        <th class="usr-bill-list-summ"><?= _t('bills', 'Сумма') ?></th>
      </tr>
      <tbody id="j-my-history-list">
        <?= $list ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ( ! $list_empty ) { ?>
  <!-- Pagination -->
  <div class="usr-pagination">
    <div id="j-my-history-pp" class="usr-pagination-dropdown dropdown">
      <a class="btn btn-default j-pp-dropdown" data-toggle="dropdown" href="#">
        <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
        <b class="caret"></b>
      </a>
      <ul class="dropdown-menu pull-right">
        <?php foreach($pgn_pp as $k=>$v): ?>
          <li><a href="#" class="<?php if($k == $f['pp']) { ?>active <?php } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div id="j-my-history-pgn">
      <?= $pgn ?>
    </div>
  </div>
  <?php } ?>
</form>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBillsMyHistory.init(<?= func::php2js(array(
      'lang' => array(),
      'ajax' => true,
      )) ?>);
  });
  <?php js::stop(); ?>
</script>