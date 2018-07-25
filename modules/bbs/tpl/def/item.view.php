<?php

    /**
     * Просмотр объявления: layout
     * @var $this BBS
     * @var $id integer ID объявления
     * @var $cats array категории объявления
     * @var $owner bool просматривает владелец объявления
     * @var $share_code string код шаринга в соц. сетях
     * @var $moderation boolean объявление находится на модерации и просматривается модератором
     * Статус объявления:
     *   @var $is_publicated boolean объявление публикуется
     *   @var $is_publicated_out boolean объявление снято с публикации
     *   @var $is_blocked boolean объявление заблокировано
     *   @var $is_soon_left boolean объявление публикуется и скоро истекает его срок публикации
     * @var $is_business boolean тип владельца - бизнес
     * @var $is_map boolean выводить карту
     * @var $contacts array контактные данные
     * @var $lang array текстовые фразы
     */

    tpl::includeJS('bbs.view', false, 7);

    tpl::includeJS('fancybox/jquery.fancybox.pack', false);
    tpl::includeJS('fancybox/helpers/jquery.fancybox-thumbs', false);
    tpl::includeCSS('/js/fancybox/jquery.fancybox', false);
    tpl::includeCSS('/js/fancybox/helpers/jquery.fancybox-thumbs', false);

    tpl::includeJS('fotorama/fotorama', false, '4.6.4.1');
    tpl::includeCSS('/js/fotorama/fotorama', false);

    if ($is_map) {
        Geo::mapsAPI(false);
    }
?>
<div class="row-fluid">
    <div class="l-page l-page_full l-page_full-left v-page span12">
        <?= tpl::getBreadcrumbs($cats, true, 'breadcrumb'); ?>
        <div class="v-page__content" id="j-view-container" itemscope itemtype="http://schema.org/Product">
            <? if($is_publicated_out): ?>
                <div class="alert-inline">
                    <div class="alert-inline__content">
                        <div class="alert alert-info rel">
                            <div>
                                <?= _t('bbs', 'Объявление снято с публикации') ?> <br />
                                <small><?= tpl::date_format2($status_changed) ?></small>
                            </div>
                            <?php if($owner) { ?><a href="#" class="btn btn-info alert-action j-item-publicate"><i class="fa fa-refresh white"></i> <?= _t('bbs', 'Опубликовать снова') ?></a><? } ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>
            <? if( $owner && $is_publicated && $is_soon_left): ?>
                <div class="alert-inline">
                    <div class="alert-inline__content">
                        <div class="alert alert-info rel">
                            <div>
                                <?= _t('bbs', 'Объявление публикуется') ?><br />
                                <small><?= _t('bbs', 'до [date]', array('date'=>tpl::date_format2($publicated_to))) ?></small>
                            </div>
                            <a href="#" class="btn btn-info alert-action j-item-refresh"><i class="fa fa-refresh white"></i> <?= _t('bbs', 'Продлить') ?></a>
                        </div>
                    </div>
                </div>
            <? endif; ?>
            <? if( $is_blocked ): ?>
                <div class="alert-inline">
                    <div class="alert-inline__content">
                        <div class="alert alert-error">
                            <?= _t('bbs', 'Объявление было заблокировано модератором.') ?><br />
                            <?= _t('bbs', 'Причина блокировки:') ?>&nbsp;<strong><?= $blocked_reason ?></strong>
                        </div>
                    </div>
                </div>
            <? endif; ?>
            <? if( $is_publicated && BBS::premoderation() && !$moderated && !$moderation): ?>
                <div class="alert-inline">
                    <div class="alert-inline__content">
                        <div class="alert alert-error">
                            <?= _t('bbs', 'Данное объявление находится на модерации.') ?><br />
                            <?= _t('bbs', 'После проверки оно будет опубликовано') ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>
            <div class="l-main l-main_maxtablet">
                <div class="l-main__content">
                    <? if(DEVICE_DESKTOP_OR_TABLET): ?>
                    <div class="hidden-phone">
                        <h1 class="v-title">
                            <? if( ! $is_publicated_out ): ?>
                                <? if($fav): ?>
                                <a href="#" class="item-fav active j-i-fav" data="{id:<?= $id ?>}" title="<?= $lang['fav_out'] ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                                <? else: ?>
                                <a href="#" class="item-fav j-i-fav" data="{id:<?= $id ?>}" title="<?= $lang['fav_in'] ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                                <? endif; ?>
                            <? endif; ?>
                            <? if($svc_quick){ ?><span class="label label-warning quickly"><?= _t('bbs', 'срочно') ?></span>&nbsp;<? } ?><b itemprop="name"><?= $title ?></b>
                        </h1>
                        <div class="v-info">
                            <small>
                                <span class="v-map-point"><i class="fa fa-map-marker"></i> <?= $city_title_delivery ?></span>
                                | <?= _t('view', 'Добавлено: [date], номер: [id]', array('date'=>tpl::date_format2($created),'id'=>$id)) ?>
                            </small>
                        </div>
                    </div>
                    <? endif; # DEVICE_DESKTOP_OR_TABLET ?>
                    <? if(DEVICE_PHONE): ?>
                    <div class="visible-phone _mobile">
                        <h1 class="v-title">
                            <? if( ! $is_publicated_out ): ?>
                                <? if($fav): ?>
                                <a href="#" class="item-fav _mobile active j-i-fav" data="{id:<?= $id ?>}" title="<?= $lang['fav_out'] ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                                <? else: ?>
                                <a href="#" class="item-fav _mobile j-i-fav" data="{id:<?= $id ?>}" title="<?= $lang['fav_in'] ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                                <? endif; ?>
                            <? endif; # ! $is_publicated_out ?>
                            <? if($svc_quick){ ?><span class="label label-warning quickly"><?= _t('bbs', 'срочно') ?></span>&nbsp;<? } ?><b itemprop="name"><?= $title ?></b><? if($price_on) { ?><? if($price) { ?>, <strong class="nowrap"><?= $price ?></strong><? } ?><? if($price_mod) { ?>, <small class="nowrap"><?= $price_mod ?></small><? } ?><? } ?>
                        </h1>
                        <div class="v-info">
                            <small>
                                <span class="v-map-point"><i class="fa fa-map-marker"></i> <?= $city_title_delivery ?></span> |
                                <? if( ! $is_publicated_out ) echo _t('view', '[date], [user], номер: [id], просмотры: [views]', array('date'=>tpl::date_format2($created),'user'=>'<a href="'.($is_shop ? $shop['link'] : $user['link']).'">'.$name.'</a>','id'=>$id,'views'=>$views_total));
                                   else echo _t('view', '[date], номер: [id], просмотры: [views]', array('date'=>tpl::date_format2($created),'id'=>$id,'views'=>$views_total));
                                 ?>
                            </small>
                        </div>
                    </div>
                    <? endif; # DEVICE_PHONE ?>
                    <div class="l-center">
                        <div class="l-center__content v-page__content_center">
                            <div class="v-descr">
                                <div class="v-descr_photos">
                                    <div class="fotorama" id="j-view-images" data-auto="false" data-controlsonstart="false">
                                    <? $i = 0;
                                       foreach($images as $v): ?>
                                        <div data-img="<?= $v['url_view'] ?>" data-thumb="<?= $v['url_small'] ?>" data-alt="<?= $v['t'] ?>" class="j-view-images-frame"><a href="javascript:;" data-zoom="<?= $v['url_zoom'] ?>" class="v-descr_photos__zoom hidden-phone j-zoom" data-index="<?= $i++; ?>"></a></div>
                                    <? endforeach;
                                    echo $this->itemVideo()->viewFotorama($video_embed);
                                    if( $is_map ) { ?><div data-thumb="<?= bff::url('/img/map_marker.gif') ?>" class="j-view-images-frame j-map"><div id="j-view-map" style="height:<?= (DEVICE_DESKTOP ? '450' : '350') ?>px; width: 100%;"></div></div><? } ?>
                                    </div>
                                </div>
                                <? if( $is_map ): ?>
                                <p class="v-descr_address">
                                    <span class="v-descr_address_attr"><?= _t('view', 'Адрес') ?>:</span>
                                    <span class="v-descr_address_val"><?= $city_title ?>, <?
                                        if($district_id && ! empty($district_data['title']) ) { echo _t('view', 'район [district]', array('district'=>$district_data['title'])).', '; } ?><?
                                        if($metro_id && ! empty($metro_data['title']) ) { echo _t('view', 'метро [station]', array('station'=>$metro_data['title'])).', '; } ?><?= $addr_addr ?>,
                                        <a href="#" class="ajax" onclick="return jView.showMap(event);"><span><?= _t('view', 'показать на карте') ?></span></a>
                                    </span>
                                </p>
                                <? elseif ($cat_addr && $metro_id && ! empty($metro_data['title'])): ?>
                                    <p class="v-descr_address">
                                        <span class="v-descr_address_attr"><?= _t('view', 'Адрес') ?>:</span>
                                        <span class="v-descr_address_val"><?= $city_title ?>, <?
                                            if ($district_id && ! empty($district_data['title']) ) { echo _t('view', 'район [district]', array('district'=>$district_data['title'])).', '; }
                                            ?><?= _t('view', 'метро [station]', array('station'=>$metro_data['title'])); ?></span>
                                    </p>
                                <? endif; # is_map  ?>
                                <div class="v-descr_properties">
                                    <ul class="unstyled"><?= $dynprops ?></ul>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="v-descr_text" itemprop="description"><?= nl2br($descr) ?></div>
                                <? if( ! $is_publicated_out && ! $owner && ( ! $moderation || ($moderation && ! $moderated)) && DEVICE_DESKTOP_OR_TABLET):
                                    /* Форма связи с автором объявления */
                                ?>
                                <div class="v-descr_contact hidden-phone">
                                    <a name="contact-form"></a>
                                    <div class="v-descr_contact_title"><?= _t('view', 'Свяжитесь с автором объявления') ?></div>
                                    <div class="v-descr_contact__form">
                                        <? if($is_shop): ?>
                                            <div class="v-descr_contact_user v-descr_contact_user_shop"><a href="<?= $shop['link'] ?>"><?= $shop['title'] ?></a></div>
                                        <? else: ?>
                                            <div class="v-descr_contact_user"><?= $name ?></div>
                                        <? endif; ?>
                                        <div class="j-v-contacts-expand-block">
                                            <? $contactsExpand = false; if( ! empty($contacts['phones']) ): ?>
                                            <div class="v-descr_contact_items">
                                                <div class="v-descr_contact_items__content v-descr_contact_items__content_phone j-c-phones">
                                                    <? foreach($contacts['phones'] as $v): ?>
                                                        <span><?= $v ?></span><? if( ! $contactsExpand ) { $contactsExpand = true; ?><a href="#" class="ajax j-v-contacts-expand-link"><?= _t('view', 'Показать контакты') ?></a><? } ?> <br />
                                                    <? endforeach; ?>
                                                </div>
                                            </div>
                                            <? endif; # phones ?>
                                            <? if(!empty($contacts['contacts'])): ?>
                                                <div class="v-descr_contact_items">
                                                    <? foreach (Users::contactsFields($contacts['contacts']) as $contact): ?>
                                                        <div class="v-descr_contact_items__title"><?= $contact['title'] ?></div>
                                                        <div class="v-descr_contact_items__content j-c-<?= $contact['key'] ?>">
                                                            <span><?= tpl::contactMask($contact['value']) ?></span>
                                                            <? if(!$contactsExpand): $contactsExpand = true; ?>
                                                                <a href="#" class="ajax j-v-contacts-expand-link"><?= _t('view', 'Показать контакты') ?></a>
                                                            <? endif; ?>
                                                        </div>
                                                    <? endforeach; ?>
                                                    <div class="clearfix"></div>
                                                </div>
                                            <? endif; ?>

                                        </div>
                                        <form action="?act=contact-form" id="j-view-contact-form">
                                            <?= Users::i()->writeForm('j-view-contact-form') ?>
                                        </form>
                                    </div>
                                </div>
                                <? endif; # DEVICE_DESKTOP_OR_TABLET ?>
                                <? if( ! $is_publicated_out && ! $owner && ! $moderation && DEVICE_PHONE): ?>
                                <div class="align-center visible-phone mrgb30">
                                    <div class="l-page__spacer l-page__spacer_top"></div>
                                    <a href="#" class="ajax j-v-contacts-expand-link"><?= _t('view', 'Показать контакты') ?></a>
                                    <div class="v-author__contact j-v-contacts-expand-block l-table">
                                        <? if( ! empty($contacts['phones']) ): ?>
                                        <div class="v-author__contact_items">
                                            <?= _t('view', 'Тел.') ?>
                                            <span class="j-c-phones">
                                                <? $i=0; foreach($contacts['phones'] as $v) { ?><?= $v ?><? if(++$i < sizeof($contacts['phones'])) { echo ', '; } } ?>
                                            </span>
                                        </div>
                                        <? endif; # phones ?>
                                        <?php if(!empty($contacts['contacts'])): ?>
                                            <?php foreach (Users::contactsFields($contacts['contacts']) as $contact): ?>
                                                <div class="v-author__contact_items">
                                                    <?= $contact['title'] ?>
                                                    <span class="j-c-<?= $contact['key'] ?>"><?= tpl::contactMask($contact['value']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; # contacts ?>
                                    </div>
                                    <div class="l-page__spacer l-page__spacer_top"></div>
                                    <div id="j-view-contact-mobile-block">
                                        <div class="v-descr_contact j-form hide">
                                            <div class="v-descr_contact__form">
                                                <form action="?act=contact-form" id="j-view-contact-mobile-form">
                                                    <?= Users::i()->writeForm('j-view-contact-mobile-form') ?>
                                                </form>
                                            </div>
                                        </div>
                                        <a href="#" class="ajax j-toggler"><?= _t('view', 'Написать сообщение') ?></a>
                                    </div>
                                    <div class="l-page__spacer l-page__spacer_top"></div>
                                    <a href="<?= ($is_shop ? $shop['link'] : $user['link']) ?>" class="ico"><span><?= ( $is_shop ? _t('view', 'Все объявления магазина') : _t('view', 'Все объявления автора') ) ?></span> <i class="fa fa-angle-double-right"></i></a>
                                    <? if ( ! empty($share_code)) { ?>
                                        <div class="l-page__spacer l-page__spacer_top"></div>
                                        <?= $share_code ?>
                                    <? } ?>
                                    <? if(bff::servicesEnabled() && BBS::itemViewPromoteAvailable($owner)) { ?>
                                    <div class="l-page__spacer l-page__spacer_top"></div>
                                    <a href="<?= $promote_url ?>" class="btn btn-success"><i class="fa fa-hand-o-up white"></i> <?= _t('view', 'Продвинуть объявление') ?></a>
                                    <? } ?>
                                </div>
                                <? endif; # DEVICE_PHONE ?>
                            </div>
                            <?
                            /* Комментарии */
                            echo $comments;
                            /* Похожие объявления */
                            if($is_publicated && !$moderation)
                                echo $similar;
                            ?>
                            <? if($bannerSimilar = Banners::view('bbs_view_similar_bottom', array('cat'=>$cat_id, 'region'=>$city_id))) { ?>
                                <div class="l-banner hidden-phone">
                                    <div class="l-banner__content">
                                        <?= $bannerSimilar; ?>
                                    </div>
                                </div>
                            <? } ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>

            <? /* Блок справа */ ?>
            <? if(DEVICE_DESKTOP_OR_TABLET): ?>
            <div class="l-right hidden-phone">
                <? if( ! $is_publicated_out): ?>
                    <? if($price_on): ?>
                    <div class="v-price<? if(empty($price_mod)) { ?> only<? } ?><? if(empty($price)) { ?> modonly<? } ?>" <? if($price) { ?>itemprop="offers" itemscope itemtype="http://schema.org/Offer"<? } ?>>
                        <? if($price) { ?><b><?= $price ?></b><? } ?><? if($price_mod) { ?><small><?= $price_mod ?></small><? } ?>
                        <? if($price_value > 0) { ?>
                            <span itemprop="priceCurrency" content="<?= mb_strtoupper(Site::currencyData($price_curr, 'keyword')) ?>"></span>
                            <span itemprop="price" content="<?= number_format($price_value, 2, '.', '') ?>"></span>
                        <? } ?>
                    </div>
                    <? endif; # $price_on  ?>
                    <?= $this->viewPHP($aData, 'item.view.owner') ?>
                    <div class="v-actions rel">
                        <a href="#" class="ico" id="j-v-send4friend-desktop-link"><i class="fa fa-user"></i> <span><?= _t('view', 'Поделиться с другом') ?></span></a> <br />
                        <div id="j-v-send4friend-desktop-popup" class="v-send4friend-popup dropdown-block dropdown-block-right box-shadow abs hide">
                            <div class="v-send4friend-popup__form">
                                <form action="" class="form-inline">
                                    <input type="hidden" name="item_id" value="<?= $id ?>" />
                                    <input type="text" name="email" class="input-medium j-required" placeholder="<?= _te('', 'E-mail') ?>" />
                                    <button type="submit" class="btn j-submit"><?= _t('', 'Отправить') ?></button>
                                </form>
                            </div>
                        </div>
                        <? if( ! $owner) { ?>
                        <a href="#" class="ico" id="j-v-claim-desktop-link"><i class="fa fa-fire"></i> <span><?= _t('view', 'Пожаловаться') ?></span></a> <br />
                        <div id="j-v-claim-desktop-popup" class="v-complaint-popup dropdown-block dropdown-block-right box-shadow abs hide">
                            <div class="v-complaint-popup__form">
                                <?= _t('item-claim', 'Укажите причины, по которым вы считаете это объявление некорректным') ?>:
                                <form action="">
                                    <? foreach($this->getItemClaimReasons() as $k=>$v):
                                           ?><label class="checkbox"><input type="checkbox" class="j-claim-check" name="reason[]" value="<?= $k ?>" /> <?= $v ?> </label><?
                                       endforeach; ?>
                                    <div class="v-complaint-popup__form_other hide j-claim-other">
                                        <?= _t('item-claim', 'Оставьте ваш комментарий') ?><br />
                                        <textarea name="comment" rows="3" autocapitalize="off"></textarea>
                                    </div>
                                    <? if( ! User::id() ): ?>
                                        <?php if (Site::captchaCustom('bbs-item-view')): ?>
                                            <?php bff::hook('captcha.custom.view', 'bbs-item-view', __FILE__); ?>
                                        <?php else: ?>
                                            <?= _t('item-claim', 'Введите результат с картинки') ?><br />
                                            <input type="text" name="captcha" class="input-small required" value="" pattern="[0-9]*" /> <img src="" alt="" class="j-captcha" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&rnd='+Math.random())" />
                                            <br />
                                        <?php endif; ?>
                                    <? endif; ?>
                                    <button type="submit" class="btn btn-danger j-submit"><?= _t('item-claim', 'Отправить жалобу') ?></button>
                                </form>
                            </div>
                        </div>
                        <? } ?>
                        <a href="?print=1" class="ico"><i class="fa fa-print"></i> <span><?= _t('view', 'Распечатать') ?></span></a> <br />
                        <? if ( ! empty($share_code)) { ?>
                        <span class="l-page__spacer mrgt15 mrgb15"></span>
                        <?= $share_code ?>
                        <? } ?>
                    </div>
                    <? if(bff::servicesEnabled() && BBS::itemViewPromoteAvailable($owner)) { ?>
                    <div class="v-adv">
                        <span class="l-page__spacer"></span>
                        <a href="<?= $promote_url ?>" class="ico"><i class="fa fa-hand-o-up"></i> <span><?= _t('view', 'Продвинуть объявление') ?></span></a> <br />
                    </div>
                    <? } ?>
                    <div class="v-stat">
                        <?= _t('view', '[views_total] объявления', array('views_total'=>tpl::declension($views_total, _t('view', 'просмотр;просмотра;просмотров')))) ?> <br />
                        <?= _t('view', '[views_today] из них сегодня', array('views_today'=>$views_today)) ?> <br />
                        <? if($views_total) { ?><a href="#" class="ajax" id="j-v-viewstat-desktop-link"><?= _t('view', 'Посмотреть статистику') ?></a><? } ?>
                        <span class="l-page__spacer l-page__spacer_empty"></span>
                        <div id="j-v-viewstat-desktop-popup-container"></div>
                    </div>
                <? endif; # ! $is_publicated_out ?>
                <? # Баннер: справа - просмотр объявления ?>
                <? if($bannerRight = Banners::view('bbs_view_right', array('cat'=>$cat_id, 'region'=>$city_id))): ?>
                <div class="l-banner banner-right">
                    <div class="l-banner__content">
                        <?= $bannerRight ?>
                    </div>
                </div>
                <? endif; # $bannerRight ?>
            </div>
            <? endif; # DEVICE_DESKTOP_OR_TABLET ?>
            <div class="clearfix"></div>
        </div>
        <div class="l-info">
            <? # TODO ?>
        </div>
    </div>
</div>
<? if( $owner ): ?>
<div class="l-action-layer fix" id="j-v-owner-panel">
    <div class="l-action-layer__navigation hidden-phone">
        <div class="l-action-layer__navigation__content">
            <div class="pull-left">
                <a href="<?= ( $from == 'my' ? 'javascript:history.back();' : BBS::url('my.items') ) ?>"><i class="fa fa-chevron-left"></i><span><?= _t('view', 'Назад в Мои объявления') ?></span></a>
            </div>
            <div class="pull-right">
                <a href="#" class="hide j-item-next"><span><?= _t('view', 'Следующее объявление') ?></span><i class="fa fa-chevron-right"></i></a>
                <span class="divider hide"></span>
                <a href="#" class="j-panel-actions-toggler" data-state="hide">
                    <span class="j-toggler-state"><span><?= _t('view', 'Закрыть') ?></span><i class="fa fa-chevron-down"></i></span>
                    <span class="j-toggler-state hide"><span><?= _t('view', 'Показать') ?></span><i class="fa fa-chevron-up"></i></span>
                </a>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
    <div class="l-action-layer__wrapper j-panel-actions">
        <div class="edit">
            <a href="<?= BBS::url('item.edit', array('id'=>$id,'from'=>'view')) ?>"><i class="fa fa-edit"></i><span><?= _t('view', 'Изменить информацию') ?></span></a>
            <? if( $is_publicated ) { ?>
            <a href="#" class="j-item-unpublicate"><i class="fa fa-times"></i><span><?= _t('view', 'Снять с публикации') ?></span></a>
            <? } ?>
            <? if( ! $is_publicated || $is_soon_left ) { ?>
                <a href="#" class="j-item-delete"><i class="fa <?= $is_soon_left ? 'fa-trash-o' : 'fa-times' ?>"></i><span><?= _t('view', 'Удалить') ?></span></a>
            <? } ?>
        </div>
        <div class="buttons">
            <a href="<?= InternalMail::url('item.messages',array('item'=>$id)) ?>" class="btn"><i class="fa fa-envelope"></i> <?= $messages_total ?><span class="hidden-phone"> <?= tpl::declension($messages_total, _t('view', 'Сообщение;Сообщения;Сообщений'), false) ?></span></a>
            <? if( $is_publicated_out ) { ?><a href="#" class="btn btn-info j-item-publicate"><i class="fa fa-arrow-up white"></i><span class="hidden-phone"> <?= _t('view', 'Активировать') ?></span></a><? } ?>
            <? if( $is_publicated && bff::servicesEnabled() ) { ?><a href="<?= $promote_url ?>" class="btn btn-success"><?= _t('view', 'Рекламировать') ?></a><? } ?>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<? endif; # owner ?>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jView.init(<?= func::php2js(array(
            'lang'=>array(
                'sendfriend'=>array(
                    'email' => _t('','E-mail адрес указан некорректно'),
                    'success' => _t('','Сообщение было успешно отправлено'),
                ),
                'claim' => array(
                    'reason_checks' => _t('item-claim','Укажите причину жалобы'),
                    'reason_other' => _t('item-claim','Опишите причину подробнее'),
                    'captcha' => _t('','Введите результат с картинки'),
                    'success' => _t('item-claim','Жалоба успешно принята'),
                ),
            ),
            'item_id'=>$id,
            'addr_lat'=>$addr_lat,
            'addr_lon'=>$addr_lon,
            'claim_other_id'=>BBS::CLAIM_OTHER,
            'mod'=>($moderation ? BBS::moderationUrlKey($id) : ''),
            'msg_success' => ! empty($msg_success) ? $msg_success : '',
            'msg_error' => ! empty($msg_error) ? $msg_error : '',
        )) ?>);
    });
<? js::stop(); ?>
</script>