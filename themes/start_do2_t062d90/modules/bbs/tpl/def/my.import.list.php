<?php
/**
 * Кабинет пользователя: Импорт объявлений - список
 * @var $this BBS
 * @var $list array список
 * @var $status array статусы
 */

$dateLast = 0;
foreach($list as $v){
    $thisDate = tpl::date_format2($v['created'], false, true);
    
?>
    <?php if( $dateLast !== $thisDate) {?>
        <tr>
            <td class="u-bill_list__date" colspan="6"><?= $thisDate ?></td>
        </tr>
    <?php } ?>
    <?php $dateLast = $thisDate; ?>
    <tr>
        <td class="u-bill__list__descr"><?= ! empty($v['cat_title']) ? $v['cat_title'] : _t('bbs.import', 'Не указана') ?></td>
        <td class="text-center"><?= $v['items_total'] ?></td>
        <td class="text-center"><?= $v['items_processed'] ?></td>
        <td><?= $v['comment_text'] ?></td>
        <td class="text-center"><span title="<?= tpl::date_format2($v['status_changed'], true, true) ?>"><?= $status[$v['status']] ?></span></td>
        <td class="text-center">
            <?php if($v['file_link']): ?><a href="<?= $v['file_link'] ?>" target="_blank"><i class="fa fa-download"></i></a><?php endif; ?>
        </td>
    </tr>
<?php
}

if(empty($list))
{ ?>
<tr>
    <td colspan="6" class="text-center" style="padding:30px;"><?= _t('bbs.import', 'История импортов пустая') ?></td>
</tr>
<?php }