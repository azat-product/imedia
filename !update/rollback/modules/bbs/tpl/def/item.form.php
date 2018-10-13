<?php

    /**
     * Форма объявления: добавление / редактирование
     * @var $this BBS
     * @var $id integer ID редактируемого объявления или 0 (при добавлении)
     * @var $userID integer ID текущего авторизованного пользователя или 0
     * @var $edit boolean редактирование (true), добавление (false)
     * @var $img BBSItemImages компонент работы с изображениями
     * @var $imagesLimit integer лимит кол-во загружаемых изображений
     * @var $imagesUploaded integer кол-во ранее загруженных изображений (при редактировании)
     * @var $contactsFromProfile boolean контакты берутся из профиля пользователя (true)
     * @var $lang array текстовые фразы
     */

    Geo::mapsAPI(true);
    tpl::includeJS(array('autocomplete','ui.sortable','qquploader'), true);
    tpl::includeJS('bbs.form', false, 12);
    $aData = HTML::escape($aData, 'html', array('title','descr','addr_addr','name','video'));
    $autoTitle = ! empty($cat_data['tpl_title_enabled']);
?>
<div class="row-fluid">
    <div class="l-page span12">
        <div class="v-page__content">
            <? # Хлебные крошки: ?>
                <?= tpl::getBreadcrumbs($breadcrumbs);
            ?>
            <div class="i-formpage">
                <h1><?= $h1 ?></h1>
                <? if($edit): ?>
                    <? switch($status){
                        case BBS::STATUS_BLOCKED: {
                            ?>
                                <div class="alert-inline">
                                <div class="alert-inline__content">
                                    <div class="alert alert-error rel">
                                        <?= _t('bbs', 'Объявление было заблокировано модератором.') ?><br />
                                        <?= _t('bbs', 'Причина блокировки:') ?>&nbsp;<strong><?= $blocked_reason ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?
                        } break;
                        case BBS::STATUS_PUBLICATED: {
                            if ((strtotime($publicated_to) - BFF_NOW) < 172800 /* менее 2 дней */):
                            ?>
                                <div class="alert-inline">
                                <div class="alert-inline__content">
                                    <div class="alert alert-info rel">
                                        <?= _t('bbs', 'Объявление опубликовано') ?><br />
                                        <small><?= _t('bbs', 'до [date]', array('date'=>tpl::date_format2($publicated_to, true))) ?></small>
                                        <a href="#" class="btn btn-info alert-action" id="j-i-form-action-refresh"><i class="fa fa-refresh white"></i> <?= _t('bbs', 'Продлить') ?></a>
                                    </div>
                                </div>
                            </div>
                            <? endif;
                        } break;
                        case BBS::STATUS_PUBLICATED_OUT: {
                            ?>
                                <div class="alert-inline">
                                <div class="alert-inline__content">
                                    <div class="alert alert-info rel">
                                        <?= _t('bbs', 'Объявление снято с публикации') ?> <br />
                                        <small><?= tpl::date_format2($publicated_to, true) ?></small>
                                        <a href="#" class="btn btn-info alert-action" id="j-i-form-action-publicate"><i class="fa fa-check white"></i> <?= _t('bbs', 'Опубликовать снова') ?></a>
                                    </div>
                                </div>
                            </div>
                            <?
                        } break;
                    } ?>
                <? endif; # $edit ?>
                <form class="form-horizontal mrgt20" action="" id="j-i-form" method="POST" enctype="multipart/form-data">
                    <? if($publisher == BBS::PUBLISHER_USER_OR_SHOP && $shop): ?>
                    <div class="control-group">
                        <label class="control-label"><?= _t('item-form', 'Разместить как') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="hidden" name="shop" value="<?= ($shop_id?1:0) ?>" class="j-publisher-type" />
                            <div class="btn-group">
                              <?
                                 foreach(array(
                                    array('id'=>0,'t'=>_t('item-form', 'Частное лицо'),'a'=>(!$shop_id), 'd'=>false),
                                    array('id'=>1,'t'=>_t('item-form', 'Магазин'),'a'=>($shop_id), 'd'=>(!$shop_data && !$shop_id)),
                                 ) as $v):
                                    ?><button type="button" class="btn<? if($v['a']){ ?> active<? } if($v['d']){ ?> disabled<? } ?> j-publisher-toggle" data-type="<?= $v['id'] ?>"><?= $v['t'] ?></button><?
                                 endforeach;
                              ?>
                            </div>
                            <? if( ! $shop_data): ?>
                                <div class="alert i-formpage__alert">
                                    <?= _t('item-form', 'Ваш магазин был <a [link]>деактивирован или заблокирован</a>.<br/>Невозможно разместить объявление от магазина.', array(
                                        'link' => 'href="'.Shops::url('my.shop').'" target="_blank"'
                                    )); ?>
                                </div>
                            <? endif; ?>
                        </div>
                    </div>
                    <? else: if($publisher == BBS::PUBLISHER_USER_TO_SHOP && $shop): ?>
                        <input type="hidden" name="shop" value="1" class="j-publisher-type" />
                    <? endif; ?>
                    <? endif; # $publisher == BBS::PUBLISHER_USER_OR_SHOP ?>
                    <div class="control-group">
                        <label class="control-label"><?= _t('item-form', 'Заголовок') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="text" name="title" value="<?= $title ?>" class="input-block-level j-required" id="j-i-title" data-limit="<?= $titleLimit ?>" autocomplete="off" <?= $autoTitle ? 'disabled="disabled"' : '' ?>/>
                            <span class="help-block<?= $autoTitle ? ' hidden' : '' ?>" id="j-i-title-maxlength"></span>
                            <span class="help-block grey extrasmall<?= !$autoTitle ? ' hidden' : '' ?>" id="j-i-title-auto"><?= _te('item-form', 'Заголовок будет сгенерирован автоматически на основе указанных вами данных') ?></span>
                        </div>
                    </div>
                    <div class="control-group control-group__100">
                        <label class="control-label"><?= _t('item-form', 'Категория') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="hidden" name="cat_id" class="j-cat-value j-required" value="<?= $cat_id ?>" />
                            <div class="i-formpage__catselect rel">
                                <div class="i-formpage__catselect__done j-cat-select-link-selected<? if( ! $cat_id) { ?> hide<? } ?>">
                                    <img class="abs j-icon" alt="" src="<?= ($cat_id ? $cat_data['icon'] : '') ?>" />
                                    <div class="i-formpage__catselect__done_cat">
                                        <a href="#" class="j-cat-select-link j-title"><?= join(' &raquo; ', $cat_path) ?></a>
                                    </div>
                                </div>
                                <? if( $edit && ! BBS::categoryFormEditable() ): ?>
                                    <div class="alert i-formpage__alert">
                                        <?= _t('item-form', 'Ваше объявление было закреплено за этой категорией.<br />Вы не можете изменить её.') ?>
                                    </div>
                                <? endif; ?>
                                <div class="i-formpage__catselect__close j-cat-select-link-empty<? if($cat_id) { ?> hide<? } ?>">
                                    <a href="#" class="ajax ajax-ico j-cat-select-link"><span><?= _t('item-form', 'Выберите категорию') ?></span> <i class="fa fa-chevron-down"></i></a>
                                </div>
                                <div class="i-formpage__catselect__popup dropdown-block box-shadow abs hide j-cat-select-popup">
                                    <div class="i-formpage__catselect__popup__content">
                                        <? if( DEVICE_DESKTOP_OR_TABLET ): ?>
                                        <div class="i-formpage__catselect__popup__mainlist j-cat-select-step1-desktop">
                                            <?= $this->catsList('form', bff::DEVICE_DESKTOP, 0); ?>
                                        </div>
                                        <div class="i-formpage__catselect__popup__sublist j-cat-select-step2-desktop hide"></div>
                                        <? endif; ?>
                                        <? if( DEVICE_PHONE ): ?>
                                        <div class="i-formpage__catselect__popup__mainlist j-cat-select-step1-phone">
                                            <?= $this->catsList('form', bff::DEVICE_PHONE, 0); ?>
                                        </div>
                                        <div class="i-formpage__catselect__popup__sublist j-cat-select-step2-phone hide"></div>
                                        <? endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="j-cat-form hide">
                        <?= ( $cat_id ? $cat_data['form'] : '' ) ?>
                    </div>
                    <div class="control-group">
                        <label class="control-label"><?= _t('item-form', 'Описание') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <textarea name="descr" class="input-block-level j-required" id="j-i-descr" data-limit="<?= $descrLimit ?>" rows="6" autocapitalize="off"><?= $descr ?></textarea>
                            <span class="help-block" id="j-i-descr-maxlength"></span>
                        </div>
                    </div>
                    <? if($publicationPeriod): ?>
                        <div class="control-group">
                            <label class="control-label"><?= _t('item-form', 'Срок публикации') ?></label>
                            <div class="controls">
                                <select class="input-medium" name="publicated_period"><?= HTML::selectOptions($publicationPeriodOpts, $publicationPeriodDays, false, 'days', 't') ?></select>
                                <span class="help-inline j-period-help">
                                    <?= _t('item-form', 'до [date]', array('date'=>tpl::date_format2(time() + $publicationPeriodDays * 86400, false, true))); ?>
                                </span>
                            </div>
                        </div>
                    <? endif; ?>
                    <div class="control-group j-video">
                        <label class="control-label"><?= _t('item-form', 'Ссылка на видео') ?></label>
                        <div class="controls">
                            <input type="text" name="video" value="<?= $video ?>" class="input-block-level j-video" maxlength="1500" />
                            <span class="help-block"><?= _t('item-form', 'Youtube, Rutube, Vimeo') ?></span>
                        </div>
                    </div>
                    <div class="control-group j-images">
                        <input type="hidden" name="images_type" value="ajax" class="j-images-type-value" />
                        <input type="hidden" name="images_hash" value="<?= $imghash ?>" />
                        <label class="control-label"><?= _t('item-form', 'Фотографии') ?><br /><small><?= _t('item-form', 'Объявления с фото получают в среднем в 3-5 раз больше откликов') ?></small></label>
                        <div class="controls">
                            <div class="i-form__addphotos j-images-type j-images-type-ajax">
                                <ul class="unstyled j-img-slots">
                                    <? for($i = 1; $i<=BBS::itemsImagesLimit(); $i++): ?>
                                    <li class="i-form__addphotos__item<? if($i>$imagesLimit){ ?> hide<? } if($i == 1) { ?> i-form__addphotos__item_first<? } ?> j-img-slot">
                                        <span class="j-img-upload">
                                            <a class="i-form__addphotos__item__plus j-img-link" href="javascript:void(0);" title="<?= $lang['image_add'] ?>">+</a>
                                        </span>
                                        <span class="j-img-preview hide">
                                            <a class="i-form__addphotos__item__del j-img-link" href="#" title="<?= $lang['image_del'] ?>">&times;</a>
                                            <a class="i-form__addphotos__item__rotate j-img-rotate" href="#" title="<?= $lang['image_rotate'] ?>"><i class="fa fa-rotate-right white"></i></a>
                                            <img src="" alt="" class="j-img-img" />
                                            <input type="hidden" name="" value="" class="j-img-fn" />
                                        </span>
                                        <span class="j-img-loading hide">
                                            <span class="i-form__addphotos__item__reload"></span>
                                        </span>
                                    </li>
                                    <? endfor; ?>
                                </ul>
                                <div class="clearfix"></div>
                            </div>
                            <div class="i-form__addphotos_simple hide j-images-type j-images-type-simple">
                                <? for($i = 1; $i<=BBS::itemsImagesLimit(); $i++): ?>
                                    <input type="file" name="images_simple_<?= $i ?>" class="<? if($i>$imagesLimit){ ?>hide <? } ?>j-img-slot" />
                                <? endfor; ?>
                            </div>
                            <span>
                                <small class="j-togglers"><?= _t('item-form', 'Если у вас возникли проблемы воспользуйтесь <a [a_simple]>альтернативной формой</a>', array('a_simple'=>' href="#" class="ajax j-toggler" data-type="simple"')) ?></a></small>
                                <small class="j-togglers hide"><a href="#" class="ajax j-toggler" data-type="ajax"><?= _t('item-form', 'Удобная форма загрузки фотографий') ?></a></small>
                            </span>
                        </div>
                    </div>
                    <div class="i-formpage__subtitle"><b><?= _t('item-form', 'Местоположение') ?></b></div>
                    <div class="l-spacer"></div>
                    <div class="j-geo">
                        <div class="control-group">
                            <label class="control-label"><?= _t('item-form', 'Город') ?><span class="required-mark">*</span></label>
                            <div class="controls">
                                <?= Geo::i()->citySelect($city_id, true, 'city_id', array(
                                    'on_change'=>'jForm.onCitySelect',
                                    'country_on_change'=>'jForm.onCountrySelect',
                                    'form' => 'bbs-form',
                                    'required' => true,
                                )); ?>
                                <span class="j-regions-delivery <? if( ! $cat_id || empty($cat_data['regions_delivery']) ) { ?>hide<? } ?>">
                                    <label class="checkbox inline">
                                        <input type="checkbox" name="regions_delivery" class="j-regions-delivery-checkbox" <? if (!empty($regions_delivery)) { ?> checked="checked"<? } ?>> <small><?= _t('item-form', 'Доставка в регионы') ?></small>
                                    </label>
                                </span>
                            </div>
                        </div>
                        <? if($districtsEnabled): ?>
                            <div class="control-group j-geo-district<?= ! $districtsVisible ? ' hide' : '' ?>">
                                <label class="control-label"><?= _t('item-form', 'Район') ?></label>
                                <div class="controls">
                                    <select name="district_id" autocomplete="off"><?= Geo::districtOptions($city_id, $district_id, _t('item-form', 'Не указан')) ?></select>
                                </div>
                            </div>
                        <? endif; ?>
                        <div class="control-group j-geo-metro<? if( ! $city_id || ! $city_data['metro']) { ?> hide<? } ?>">
                            <input type="hidden" name="metro_id" value="<?= $metro_id ?>" class="j-geo-metro-value" />
                            <label class="control-label"><?= _t('item-form', 'Метро') ?></label>
                            <div class="controls rel">
                                <div class="i-formpage__metroselect">
                                    <div class="i-formpage__metroselect__close j-geo-metro-link-empty<? if($metro_id) { ?> hide<? } ?>">
                                        <a href="#" class="ajax ajax-ico j-geo-metro-link"><span><?= _t('item-form', 'Выберите станцию') ?></span> <i class="fa fa-chevron-down"></i></a>
                                    </div>
                                    <div class="i-formpage__metroselect__done j-geo-metro-link-selected<? if(!$metro_id) { ?> hide<? } ?>">
                                        <div class="i-formpage__metroselect__done_cat">
                                            <span class="i-formpage__metroselect__item inlblk j-color" style="background-color:<?= ( $metro_id ? $metro_data['sel']['branch']['color'] : '' ) ?>;"></span>
                                            <a href="#" class="j-geo-metro-link j-title"><?= ( $metro_id ? $metro_data['sel']['branch']['t'] . ' &raquo; ' . $metro_data['sel']['station']['t'] : '' ) ?></a>
                                            <a href="javascript:void(0);" class="cancel<? if(!$metro_id) { ?> hide<? } ?> j-geo-metro-cancel"><i class="fa fa-times"></i></a>
                                        </div>
                                    </div>
                                    <div class="i-formpage__metroselect__popup dropdown-block box-shadow abs hide j-geo-metro-popup">
                                        <div class="i-formpage__metroselect__popup__mainlist j-step1"></div>
                                        <div class="i-formpage__metroselect__popup__sublist j-step2 hide"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="j-i-geo-addr"<? if( ! $cat_id || ! $cat_data['addr'] ) { ?> class="hide"<? } ?>>
                            <div class="control-group">
                                <label class="control-label"><?= _t('item-form', 'Адрес объекта') ?></label>
                                <div class="controls">
                                    <input type="hidden" name="addr_lat" id="j-i-geo-addr-lat" value="<?= $addr_lat ?>" />
                                    <input type="hidden" name="addr_lon" id="j-i-geo-addr-lon" value="<?= $addr_lon ?>" />
                                    <input type="text" name="addr_addr" class="input-block-level" id="j-i-geo-addr-addr" value="<?= $addr_addr ?>" />
                                    <span class="help-block"><?= _t('item-form', 'Укажите улицу, район, номер дома и т.п.') ?></span>
                                </div>
                            </div>
                            <div class="control-group">
                                <div class="controls">
                                    <div id="j-i-geo-addr-map" style="height: 250px; width: 100%; max-width: 470px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="i-formpage__subtitle j-i-contacts-block"><b><?= _t('item-form', 'Ваши контактные данные') ?></b></div>
                    <div class="l-spacer"></div>
                    <? if($contactsFromProfile) { ?>
                        <div class="alert">
                            <? if ($edit) { ?>
                                <?= _t('item-form', 'Ваши контактные данные для объявления берутся из <a [link_settings]>настроек</a> в личном кабинете.', array(
                                    'link_settings' => 'href="'.Users::url('my.settings').'" target="_blank"'
                                )) ?>
                            <? } else { ?>
                                <?= _t('item-form', 'Изменить ваши контактные данные вы можете в личном кабинете в разделе <a [link_settings]>настройки</a>.', array(
                                    'link_settings' => 'href="'.Users::url('my.settings').'" target="_blank"'
                                )) ?>
                            <? } ?>
                        </div>
                    <? } ?>
                    <div class="control-group<? if(($shop && $publisher_only_shop) || ($contactsFromProfile && $edit)){ ?> hide<? } ?>" id="j-i-name-block">
                        <label class="control-label"><?= _t('item-form', 'Контактное лицо') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="text" name="name" class="input-xlarge<? if(!$contactsFromProfile) { ?> j-required<? } ?>" value="<?= $name ?>"<? if($contactsFromProfile) { ?> readonly="readonly"<? } ?> placeholder="" id="j-i-name" maxlength="50" />
                            <span class="help-block"><?= _t('item-form', 'Имя появится в блоке с контактной информацией') ?></span>
                        </div>
                    </div>
                    <div class="j-cat-owner">
                        <?= ( $cat_id ? $cat_data['owner'] : '' ) ?>
                    </div>
                    <? if(Users::registerPhone() && !$edit) { ?>
                    <div class="control-group">
                        <label class="control-label"><?= _t('item-form', 'Номер телефона') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <? if(empty($phone_number) || !$phone_number_verified) { ?>
                                <?= $this->users()->registerPhoneInput(array('name'=>'phone', 'value'=>(!empty($phone_number) ? '+'.$phone_number : '')), array('item-form'=>true)) ?>
                            <? } else { ?>
                                <input type="text" class="input-xlarge" value="+<?= HTML::escape($phone_number) ?>" disabled="disabled" />
                                <i class="fa fa-check text-success hidden-phone"></i>
                            <? } ?>
                        </div>
                    </div>
                    <? } ?>
                    <div class="control-group<? if($edit) { ?> hide<? } ?>">
                        <label class="control-label"><?= _t('item-form', 'E-mail адрес') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="email" name="email" value="<?= ( ! empty($email) ? HTML::escape($email) : '' ) ?>" <? if($userID) { ?> readonly="readonly"<? } ?> class="input-xlarge<?= ! $userID ? ' j-required' : '' ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                        </div>
                    </div>
                    <div class="control-group<? if($contactsFromProfile && $edit) { ?> hide<? } ?>">
                        <label class="control-label"><?= _t('item-form', 'Контакты') ?></label>
                        <div class="controls">
                            <div class="i-formpage__contacts">
                                <div id="j-i-phones"></div>
                                <? foreach (Users::contactsFields() as $contact): ?>
                                    <div class="i-formpage__contacts__item">
                                        <div class="input-prepend">
                                            <span class="add-on"><i class="<?= $contact['icon'] ?>"></i></span>
                                            <input type="text"
                                                   name="contacts[<?= $contact['key'] ?>]"
                                                   value="<?= isset($contacts[$contact['key']]) ? HTML::escape($contacts[$contact['key']]) : '' ?>"
                                                   class="input-large j-c-<?= $contact['key'] ?>" <?= $contactsFromProfile ? 'readonly="readonly"' : '' ?>
                                                   placeholder="<?= $contact['title'] ?>"
                                                   maxlength="<?= $contact['maxlength'] ?>"
                                            />
                                        </div>
                                    </div>
                                <? endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <? if($servicesAvailable) { ?>
                    <div class="j-svc-block" style="display:none;">
                        <div class="i-formpage__subtitle"><b><?= _t('item-form', 'Продвижение объявления') ?></b></div>
                        <div class="l-spacer"></div>
                        <div class="control-group">
                            <div class="controls">
                                <div class="i-formpage__promotion">
                                    <? foreach($svc_data as $v) {
                                        if (empty($v['add_form'])) continue;
                                    ?>
                                    <div class="i-formpage__promotion__item j-svc-item j-svc-<?= $v['id'] ?>">
                                        <label>
                                            <div class="i-formpage__promotion__item__title" style="background-color: <?= $v['color'] ?>">
                                                <label class="radio">
                                                    <input type="radio" name="svc" value="<?= $v['id'] ?>" />
                                                    <div class="i-formpage__promotion__item__icon"><img src="<?= $v['icon_s'] ?>" alt="" /></div><?= $v['title_view'] ?> <span class="pull-right"><b class="j-price"><?= tpl::currency($v['price']) ?></b> <?= $curr ?></span>
                                                </label>
                                            </div>
                                            <div class="i-formpage__promotion__item__descr hide j-svc-descr">
                                                <? if($v['id'] == BBS::SERVICE_FIX && ! empty($v['period_type']) && $v['period_type'] == BBS::SVC_FIX_PER_DAY){ ?>
                                                    <?= _t('bbs', 'Закрепить на [input] день', array('input'=>
                                                        '<input class="input-mini text-center" value="'.config::sysAdmin('bbs.services.fix.days.default', 1, TYPE_UINT).'" type="number" name="fix_days" min="1" max="999" />'
                                                    ))?>
                                                    <div class="l-spacer"></div>
                                                <? } ?>
                                                <?= nl2br($v['description']); ?>
                                            </div>
                                        </label>
                                    </div>
                                    <? } ?>
                                    <div class="i-formpage__promotion__item active last i-promotion_free j-svc-item j-svc-0">
                                        <label>
                                        <div class="i-formpage__promotion__item__title">
                                            <label class="radio">
                                                <input type="radio" name="svc" value="0" checked="checked" />
                                                <div class="i-formpage__promotion__item__icon"><img src="<?= bff::url('/img/square-grey.png') ?>" alt="" /></div><?= _t('item-form', 'Бесплатное объявление') ?> <span class="pull-right"></span>
                                            </label>
                                        </div>
                                        <div class="i-formpage__promotion__item__descr j-svc-descr">
                                            <?= _t('item-form', 'Бесплатное объявление, ничем не выделено на фоне таких же предложений') ?>
                                        </div>
                                        </label>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <? } ?>
                    <? if($agreementAvailable): ?>
                    <div class="control-group">
                        <div class="controls">
                            <label class="checkbox inline">
                                <input name="agree" type="checkbox" class="j-required" /> <small><?= _t('item-form', 'Я соглашаюсь с <a [link_agreement]>правилами использования сервиса</a>, а также с передачей и обработкой моих данных в [site_title]. Я подтверждаю своё совершеннолетие и ответственность за размещение объявления', array('link_agreement'=>'href="'.Users::url('agreement').'" target="_blank"','site_title'=>Site::title('bbs.item.form.agreement'))) ?> <span class="required-mark">*</span></small>
                            </label>
                        </div>
                    </div>
                    <? endif; ?>
                    <div class="l-spacer l-spacer_empty"></div>
                    <div class="control-group">
                        <? if ($edit) { ?>
                        <label class="control-label hidden-phone text-right">
                            <div class="i-formpage__cancel">
                                <span class="btn-link j-cancel">« <?= _t('', 'Отмена') ?></span>
                            </div>
                        </label>
                        <? } ?>
                        <div class="controls">
                            <input type="submit" class="btn btn-success j-submit" value="<?= ( $edit ? _t('item-form', 'Изменить') : _t('item-form', 'Опубликовать объявление') ) ?>" data-loading-text="<?= _te('item-form', 'Подождите...') ?>" />
                            <? if($edit){ ?><span class="i-formpage__cancel_mobile btn-link j-cancel">&nbsp;&nbsp; <?= _t('', 'Отмена') ?></span> <? } ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        <?
            $jsSettings = array(
                # item
                'itemID' => $id,
                'edit' => $edit,
                # category
                'catsRootID' => BBS::CATS_ROOTID,
                'catsMain' => $this->catsList('form', 'init'),
                'catTypesEx' => (BBS::CATS_TYPES_EX ? true : false),
                'catTypeSeek' => BBS::TYPE_SEEK,
                'catEditable' => BBS::categoryFormEditable(),
                # images
                'imgLimit' => $imagesLimit,
                'imgMaxSize' => $img->getMaxSize(),
                'imgUploaded' => $imagesUploaded,
                'imgData' => $images,
                # geo
                'geoCityID' => $city_id,
                # user
                'phonesLimit' => Users::i()->profilePhonesLimit,
                'phonesData' => $phones,
                'contactsFromProfile' => $contactsFromProfile,
                'owner_types' => array('private' => BBS::OWNER_PRIVATE, 'business' => BBS::OWNER_BUSINESS),
                # lang
                'lang' => array(
                    'maxlength_symbols_left' => _t('', '[symbols] осталось'),
                    'maxlength_symbols' => _t('', 'знак;знака;знаков'),
                    'upload_typeError' => _t('item-form', 'Допустимы только следующие типы файлов: {extensions}'),
                    'upload_sizeError' => _t('item-form', '"Файл {file} слишком большой, максимально допустимый размер {sizeLimit}'),
                    'upload_minSizeError' => _t('item-form', 'Файл {file} имеет некорректный размер'),
                    'upload_emptyError' => _t('item-form', 'Файл {file} имеет некорректный размер'),
                    'upload_limitError' => _t('item-form', 'Вы можете загрузить не более {limit} изображений'),
                    'upload_onLeave' => _t('item-form', 'Происходит загрузка изображения, если вы покинете эту страницу, загрузка будет прекращена'),
                    'email_wrong' => _t('users', 'E-mail адрес указан некорректно'),
                    'phones_tip' => _t('item-form', 'Номер телефона'),
                    'phones_plus' => _t('item-form', '+ ещё<span [attr]> телефон</span>', array('attr'=>'class="hidden-phone"')),
                    'phones_req' => _t('item-form', 'Укажите номер телефона'),
                    'price' => _t('item-form', 'Укажите цену'),
                ),
                'autoTitle' => $autoTitle,
                'catLastTitle' => ! empty($cat_path) ? end($cat_path) : '',
            );
            if ($city_id) {
                $cityData = Geo::model()->regionData(array('id' => $city_id), true);
                if ( ! empty($cityData['declension'][LNG])) {
                    $jsSettings['geoCityDeclension'] = $cityData['declension'][LNG];
                }
            }
            # при добавлении с возможностью выбора типа "частное лицо/магазина" - формируем данные о контактах
            if ( ! $edit && $publisher == BBS::PUBLISHER_USER_OR_SHOP && $shop && $shop_data)
            {
                $jsSettings['contacts_shop'] = &$shop_data;
                $jsSettings['contacts_shop_phones'] = &$shop_data['phones'];
                $jsSettings['contacts_user'] = $contacts;
                $jsSettings['contacts_user_phones'] = $phones;
                foreach ($shop_data as $k=>&$v) {
                    if ( ! isset($aData[$k])) continue;
                    $jsSettings['contacts_user'][$k] = &$aData[$k];
                    if ($k == 'city_data') {
                        $v = array('title'=>$v['title'],'metro'=>!empty($v['metro']),'pid'=>$v['pid'],'declension'=>$v['declension']);
                        $aData[$k] = array('title'=>$aData[$k]['title'],'metro'=>!empty($aData[$k]['metro']),'pid'=>$aData[$k]['pid'],'declension'=>$aData[$k]['declension']);
                    }
                } unset($v);
            }
            if ($publicationPeriod) {
                foreach ($publicationPeriodOpts as & $v) {
                    $v = _t('item-form', 'до [date]', array('date'=>tpl::date_format2(time() + $v['days'] * 86400, false, true)));
                } unset($v);
                $jsSettings['periods'] = $publicationPeriodOpts;
            }
        ?>
        jForm.init(<?= func::php2js($jsSettings) ?>);
    });
<? js::stop(); ?>
</script>