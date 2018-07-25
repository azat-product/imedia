<?php
    $statuses = Sendmail::statuses();
?>
<table class="table table-condensed table-hover admtbl tblhover">
    <thead>
    <tr class="header">
        <th width="60"><?= _t('', 'ID'); ?></th>
        <th><?= _t('sendmail', 'Получателей'); ?></th>
        <th><?= _t('sendmail', 'Отправлено'); ?></th>
        <th><?= _t('sendmail', 'Начало'); ?></th>
        <th width="200"><?= _t('sendmail', 'Статус'); ?></th>
        <th width="90"></th>
    </tr>
    </thead>
    <? foreach($items as $k=>$v) { $ID = $v['id']; ?>
        <tr class="row<?= $k%2 ?>" id="ms<?= $ID ?>">
            <td><?= $ID ?></td>
            <td><span><?= $v['total'] ?></span></td>
            <td><span class="clr-success"><?= $v['success'] ?></span><span class="desc"> / </span><span class="clr-error"><?= $v['fail'] ?></span></td>
            <td><?= tpl::date_format2($v['started'], true) ?></td>
            <td><?= isset($statuses[ $v['status'] ]) ? $statuses[ $v['status'] ]['t'] : (!$v['status'] ? _t('sendmail', 'незавершена') : tpl::date_format2($v['finished'], true) ) ?>
                <?= $v['status'] == Sendmail::STATUS_FINISHED ? tpl::date_format2($v['finished'], true) : '' ?>
            <td>
                <? switch ($v['status']):
                    case Sendmail::STATUS_SCHEDULED:
                    case Sendmail::STATUS_PROCESSING:
                        ?><a class="but icon-pause disabled j-pause" title="<?= _te('', 'Поставить на паузу') ?>" href="javascript:" data-id="<?= $ID ?>"></a><? break;
                    case Sendmail::STATUS_PAUSED:
                    case Sendmail::STATUS_PAUSE_BEGIN:
                        ?><a class="but icon-play disabled j-continue" title="<?= _te('', 'Продолжить') ?>" href="javascript:" data-id="<?= $ID ?>"></a><? break;
                    default:
                        ?><a class="but" href="javascript:"></a><? break;
                endswitch; ?>
                <a class="but edit j-info" title="<?= _te('sendmail', 'Подробности'); ?>" href="javascript:" data-id="<?= $ID ?>"></a>
                <a class="but del j-delete" title="<?= _te('', 'Delete') ?>" href="javascript:" data-id="<?= $ID ?>"></a>
            </td>
        </tr>
    <? } if( empty($items) ) { ?>
        <tr class="norecords">
            <td colspan="6"><?= _t('sendmail', 'нет рассылок'); ?></td>
        </tr>
    <? } ?>
</table>
