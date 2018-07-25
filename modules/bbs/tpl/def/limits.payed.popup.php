<?php
/**
 * Платные лимиты: попап действующих лимитов (оплаченных ранее)
 * @var $this BBS
 * @var $limits array действующие лимиты
 */

?>
<div class="modal modal-lg hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3><?= _t('bbs', 'Вы достигли лимита в следующих разделах:'); ?></h3>
    </div>
    <div class="modal-body">

        <? foreach($limits as $v): if($v['cnt'] < $v['limit']) continue; ?>
        <div class="row-fluid l-limitEnd">
            <div class="l-limitEnd__left">
                <div class="l-limitEnd__categories">
                    <strong><?= $v['parent'] ?></strong><br>
                    <?= $v['title'] ?>
                </div>
                <a class="btn btn-success" href="<?= BBS::url('limits.payed', array('point' => $v['point'], 'shop' => $shop)) ?>"><?= _t('bbs', 'Расширить лимит'); ?></a>
            </div>
            <div class="l-limitEnd__right">

                <div class="l-limitEnd__item l-limitEnd__active">
                    <div class="l-limitEnd__item__num">
                        <?= $v['cnt'] ?>
                    </div>
                    <div class="l-limitEnd__item__content">
                        <?= _t('bbs', 'Активных объявлений в данный момент'); ?>
                    </div>
                </div>
                <div class="l-limitEnd__item l-limitEnd__total">
                    <div class="l-limitEnd__item__num">
                        <?= $v['limit'] ?>
                    </div>
                    <div class="l-limitEnd__item__content">
                        <?= _t('bbs', 'Лимит объявлений'); ?>
                    </div>
                </div>
            </div>
        </div>
        <? endforeach; ?>

    </div>
</div>