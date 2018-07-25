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
<div id="j-v-viewstat-desktop-popup" class="l-popup v-stat-popup box-shadow hide">
    <div class="l-popup__close j-close"><i class="fa fa-times"></i></div>
    <div class="l-popup__content">
        <h2 class="l-popup__content_title v-stat-popup__title align-center"><?= _t('view', 'Статистика просмотров объявления за месяц') ?></h2>
        <? /* graph */ ?>
        <div class="v-stat-popup__graph" id="j-v-viewstat-desktop-popup-chart"></div>
        <div class="v-stat-popup__info">
            <p>
                <?= _t('view', 'Просмотров сегодня') ?>: <?= $today ?> <br />
                <?= _t('view', 'Просмотров всего') ?>: <?= $total ?>
            </p>
            <p><? if( $from == $to ) { ?>
                    <?= _t('bbs', 'За [from]', array('from'=>tpl::date_format2($from))) ?>
                <? } else { ?>
                    <?= _t('bbs', 'С [from] по [to]', array('from'=>tpl::date_format2($from),'to'=>tpl::date_format2($to))) ?>
                <? } ?>
            </p>
            <? if(bff::servicesEnabled() && BBS::itemViewPromoteAvailable($owner)) { ?>
            <div class="v-stat-popup__info_stiker">
                <?= _t('view', 'Хотите, чтобы ваше <br /> объявление <br /> увидело больше людей?') ?>
            </div>
            <div class="v-stat-popup__info_btn">
                <a href="<?= $promote_url ?>" class="btn btn-success"><i class="fa fa-hand-o-up white"></i> <?= _t('view', 'Продвиньте объявление') ?></a>
            </div>
            <? } ?>
        </div>
        <div class="clearfix"></div>
    </div>
</div>