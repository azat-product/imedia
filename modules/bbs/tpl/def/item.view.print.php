<?php
/**
 * Просмотр объявления для печати
 * @var $this BBS
 * @var $title string заголовок объявления
 * @var $dynprops string дин. свойства (HTML)
 * @var $descr string описание
 */

?>
<div class="l-page v-page v-page_print">
    <div class="v-page__content">
        <a class="logo" href="<?= bff::urlBase() ?>"><img src="<?= Site::logoURL('bbs.item.view.print') ?>" alt="" /> <?= Site::titleHeader('bbs.item.view.print') ?></a>
        <div class="v-descr">
            <div class="v-page_print_left pull-left">
                <h1><b><?= $title ?></b></h1>
                <? if($price_on) { ?>
                <div class="v-price-print">
                    <b><?= $price ?></b>, <small><?= $price_mod ?></small>
                </div>
                <? } ?>
                <div class="v-info">
                    <small>
                        <?= $city_title ?> | <?= _t('view', 'Добавлено: [date], номер: [id]', array('date'=>tpl::date_format2($created),'id'=>$id)) ?>
                    </small>
                </div>
                <div class="v-descr_photos">
                    <? foreach($images as $v): ?>
                        <img src="<?= $v['url_view'] ?>" alt="<?= $v['t'] ?>" />
                    <? break; endforeach; ?>
                </div>
            </div>
            <div class="v-author v-author_print<? if($is_shop){ ?> v-author_shop<? } ?> pull-left">
                <? if($is_shop) { ?>
                <span class="v-author__avatar">
                    <img src="<?= $shop['logo'] ?>" class="img" alt="<?= $shop['title'] ?>" />
                </span>
                <? } ?>
                <div class="v-author__info">
                    <span><?= $name ?></span><br />
                </div>
                <div class="clearfix"></div>
                <div class="v-author__contact">
                    <div class="v-author__contact__title"><span><?= _t('view', 'Контакты:') ?></span></div>
                    <? if( ! empty($phones) ): ?>
                    <div class="v-author__contact_items">
                        <div class="v-author__contact_title"><?= _t('view', 'Тел.') ?></div>
                        <div class="v-author__contact_content">
                            <?= Users::phonesView($phones, false); ?>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <? endif; ?>
                    <? if(!empty($contacts['contacts'])): ?>
                        <div class="v-author__contact_items">
                            <? foreach (Users::contactsFields($contacts['contacts']) as $contact): ?>
                                <div class="v-author__contact_title"><?= $contact['title'] ?></div>
                                <div class="v-author__contact_content">
                                    <span><?= HTML::obfuscate($contact['value']) ?></span>
                                </div>
                            <? endforeach; ?>
                            <div class="clearfix"></div>
                        </div>
                    <? endif; ?>
                </div>
            </div>
            <div class="clearfix"></div>
            <p class="v-descr_address">
                <span class="v-descr_address_attr"><?= _t('view', 'Адрес') ?>:</span>
                <span class="v-descr_address_val"><?= $city_title ?>, <?
                    if ($district_id && ! empty($district_data['title']) ) { echo _t('view', 'район [district]', array('district'=>$district_data['title'])).', '; } ?><?
                    if ($metro_id && ! empty($metro_data['title']) ) { echo _t('view', 'метро [station]', array('station'=>$metro_data['title'])).', '; } ?><?= $addr_addr ?></span>
            </p>
            <div class="v-descr_properties">
                <ul class="unstyled"><?= $dynprops ?></ul>
                <div class="clearfix"></div>
            </div>
            <div class="v-descr_text">
                <?= nl2br($descr) ?>
            </div>
        </div>
    </div>
</div>
<div id="printBtn" class="l-action-layer l-action-layer_print fix">
    <div class="l-action-layer__wrapper">
        <button class="btn" onclick="window.print();"><i class="fa fa-print"></i> <?= _t('view', 'Распечатать') ?></button>
        <a class="cancel" href="javascript:void(0);" onclick="history.back();"><?= _t('', 'Отмена') ?></a>
    </div>
</div>