<?php
/**
 * Кабинет: Счет - история счетов - список
 * @var $this Bills
 * @var $list array данные о счетах
 * @var $curr string валюты сайта
 */

$langNumber = _t('bills', '№ операции');

$dateLast = 0;
foreach($list as $v)
{
  $in = ($v['type'] != Bills::TYPE_OUT_SERVICE);
  ?>
  <?php if( $dateLast !== $v['created_date']) { ?>
  <tr>
    <td class="usr-bill-list-date" colspan="3"><?= tpl::datePublicated($v['created_date'], 'Y-m-d', false, ' ') ?></td>
  </tr>
  <?php } ?>
  <?php $dateLast = $v['created_date']; ?>
  <tr>
    <td>
      <?= $v['description'] ?><div class="small text-muted"><?= $langNumber.' '.$v['id'] ?></div>
    </td>
    <td class="usr-bill-list-id"><?= $v['id'] ?></td>
    <td class="usr-bill-list-summ"><?= ( ! $v['amount'] ? '&mdash;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : ( ! $in ? '- ' : '').$v['amount'].' '.$curr ) ?></td>
  </tr>
  <?php
}

if(empty($list))
  { ?>
<tr>
  <td colspan="3" class="text-center" style="padding:30px;"><?= _t('bills', 'Список операций по счету пустой') ?></td>
</tr>
<?php }