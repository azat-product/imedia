<?php
/**
 * Платные лимиты: попап действующих лимитов (оплаченных ранее)
 * @var $this BBS
 * @var $limits array действующие лимиты
 */

?>
<div class="modal fade" id="limitsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title"><?= _t('bbs', 'Вы достигли лимита в следующих разделах:'); ?></h4>
      </div>
      <div class="modal-body">

        <?php foreach($limits as $v): if($v['cnt'] < $v['limit']) continue; ?>
        <div class="l-limitEnd">
          <div class="l-limitEnd-left">
            <div class="l-limitEnd-categories">
              <strong><?= $v['parent'] ?></strong><br>
              <?= $v['title'] ?>
            </div>
            <a class="btn btn-success" href="<?= BBS::url('limits.payed', array('point' => $v['point'], 'shop' => $shop)) ?>"><?= _t('bbs', 'Расширить лимит'); ?></a>
          </div>
          <div class="l-limitEnd-right">

            <div class="l-limitEnd-item l-limitEnd-active">
              <div class="l-limitEnd-item-num">
                <?= $v['cnt'] ?>
              </div>
              <div class="l-limitEnd-item-content">
                <?= _t('bbs', 'Активных объявлений в данный момент'); ?>
              </div>
            </div>
            <div class="l-limitEnd-item l-limitEnd-total">
              <div class="l-limitEnd-item-num">
                <?= $v['limit'] ?>
              </div>
              <div class="l-limitEnd-item-content">
                <?= _t('bbs', 'Лимит объявлений'); ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>