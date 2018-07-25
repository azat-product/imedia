<?php
/**
 * Просмотр объявления: всплывающее окно просмотра статистики
 * @var $this BBS
 * @var $today integer кол-во просмотров за сегодня
 * @var $total integer кол-во просмотров всего
 * @var $from string дата "от"
 * @var $to string дата "до"
 * @var $promote_url string URL на страницу продвижения объявления
 * @var $owner bool просматривает владелец объявления
 */

?>
<div class="modal j-popup">
  <div class="modal-dialog modal-dialog-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"
            id="exampleModalLabel"><?= _t('view', 'Статистика просмотров объявления за месяц') ?></h4>
      </div>
      <div class="modal-body">
        <div class="l-stats">
          <div class="l-stats-graph" id="j-v-viewstat-desktop-popup-chart"></div>
          <div class="l-stats-info">
            <p>
              <?= _t('view', 'Просмотров сегодня') ?>: <strong><?= $today ?></strong>
            </p>
            <p>
              <?= _t('view', 'Просмотров всего') ?>: <strong><?= $total ?></strong>
            </p>
            <p>
              <?php if ($from == $to) { ?>
                <?= _t('bbs', 'За [from]', array('from' => tpl::date_format2($from))) ?>
              <?php } else { ?>
                <?= _t('bbs', 'С [from] по [to]', array('from' => tpl::date_format2($from), 'to' => tpl::date_format2($to))) ?>
              <?php } ?>
            </p>
            <?php if (bff::servicesEnabled() && BBS::itemViewPromoteAvailable($owner)) { ?>
              <div class="l-stats-info-promo">
                <div class="l-stats-info-promo-title">
                  <?= _t('theme.nelson', 'Хотите, чтобы ваше объявление увидело больше людей?') ?>
                </div>
                <a href="<?= $promote_url ?>" class="btn btn-success btn-block"><i
                    class="fa fa-rocket"></i> <?= _t('view', 'Продвиньте объявление') ?></a>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>