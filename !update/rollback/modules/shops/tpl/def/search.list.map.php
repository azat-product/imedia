<?php
/**
 * Поиск магазинов: список - карта
 * @var $this Shops
 * @var $items array список магазинов
 * @var $device string тип устройства bff::DEVICE_
 */

$is_ajax = Request::isAJAX();

if($device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET) { ?>
    <? if( ! $is_ajax ) {
        if (empty($items)) {
            echo $this->showInlineMessage(_t('shops', 'Список магазинов пустой'));
            return;
        }
    ?>
    <div class="sr-page__map sr-page__map_desktop rel">
        <div class="row-fluid">
            <div class="sr-page__map__list span5 j-maplist">
    <? } ?>
            <? foreach($items as $k=>&$v) { ?>
            <div class="sr-page__map__list__item sh-page__map__list__item rel j-maplist-item" data-index="<?= $k ?>">
                <span class="num"><?= $v['num'] ?>.</span>
                <h5>
                    <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
                </h5>
                <p>
                    <small><?= tpl::truncate($v['descr'], 150, '...', true) ?></small>
                </p>
            </div>
            <? } unset($v); ?>
    <? if( ! $is_ajax ) { ?>
            </div>
            <div class="sr-page__map_ymap span7 j-map" data-short-class="span7">
                <div class="sr-page__map__controls">
                    <span id="j-search-map-toggler"><span class="j-search-map-toggler-arrow">&laquo;</span> <a href="#" class="ajax j-search-map-toggler-link"><?= _t('search', 'больше карты'); ?></a></span>
                </div>
                <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
            </div>
        </div>
        <div class="row-fluid hide">
            <div class="sr-page__map_tablet_listarrow span5 visible-tablet">
                <a href="#" class="sr-page__map__list__item_down block " >
                    <i class="fa fa-chevron-down"></i>
                </a>
            </div>
        </div>
    </div>
    <? } ?>
<? } else if($device == bff::DEVICE_PHONE) { ?>
    <? if( ! $is_ajax ) { ?>
        <div class="sr-page__map sr-page__map_mobile visible-phone">
            <div class="sr-page__map_ymap span12">
                <div class="j-search-map-phone" style="height: 350px; width: 100%;"></div>
            </div>
        </div>
    <? } ?>
<? }