<?php
/**
 * Страница магазина: layout
 * @var $this Shops
 * @var $shop array данные о магазине
 * @var $has_owner boolean у магазина есть владелец
 * @var $is_owner boolean является ли текущий пользователь владельцем просматриваемого магазина
 * @var $share_code string код шаринга в соц. сетях
 * @var $url_promote string URL на страницу продвижения магазина
 * @var $url_promote_visible boolean отображать ссылку продвижения магазина
 * @var $request_form_visible boolean отображать форму подачи заявки на представительство
 */
     tpl::includeJS('shops.view', false, 5);
?>
<div class="row-fluid">
    <div class="l-page l-page_full l-page_full-left sh-page span12">
        <div class="sh-page__content" id="j-shops-v-container">

            <? # Хлебные крошки: ?>
            <?= tpl::getBreadcrumbs($breadcrumbs); ?>

            <div class="l-main l-main_maxtablet">
                <div class="l-main__content">
                    <? if(DEVICE_PHONE): ?>
                        <div class="sh-page_mobile visible-phone">
                            <div class="v-author v-author_shop">
                                <? if($shop['logo']) { ?>
                                <a href="<?= $shop['link'] ?>" class="v-author__avatar">
                                    <img src="<?= $shop['logo_small'] ?>" class="img" alt="" />
                                    </a><? } ?>
                                <div class="v-author__info">
                                    <strong><?= $shop['title'] ?></strong>
                                    <div class="v-author_shop__descr">
                                        <? if($shop['descr_visible_limit'] > 0 && mb_strlen($shop['descr']) > $shop['descr_visible_limit']) { ?>
                                            <div><?= tpl::truncate($shop['descr'], $shop['descr_visible_limit'] , '', true) ?><a href="#" class="ajax v-author_shop__descr_expand" id="j-shop-view-descr-ex-phone">...</a></div>
                                            <div class="hide"><?= mb_substr($shop['descr'], $shop['descr_visible_limit']); ?></div>
                                        <? } else { ?>
                                            <?= $shop['descr']; ?>
                                        <? } ?>
                                    </div>
                                    <? if( ! empty($shop['site'])) { ?><div class="v-author_shop__link"><a href="<?= bff::urlAway($shop['site']) ?>" target="_blank" rel="nofollow noopener" class="ico hide-tail j-away"><i class="fa fa-globe"></i> <span><?= $shop['site'] ?></span></a></div><? } ?>
                                    <? if( ! empty($shop['addr_addr'])) { ?>
                                        <div class="v-author_shop__address rel">
                                            <i class="fa fa-map-marker"></i> 
                                            <?php if ($shop['addr_map']): ?><a href="#" id="j-shop-view-phone-map-toggler" class="ajax"><?= $shop['region_title'].', '.$shop['addr_addr'] ?></a><?php else: ?><?= $shop['region_title'].', '.$shop['addr_addr'] ?><?php endif; ?>
                                        </div>
                                        <?php if ($shop['addr_map']) {
                                            Geo::mapsAPI(false);
                                        ?>
                                        <div id="j-shop-view-phone-map" class="hide" style="width:100%;height:400px; margin-top: 10px;"></div>
                                        <? } ?>
                                    <? } ?>
                                </div>
                                <div class="clearfix"></div>
                                <? if($shop['has_contacts']): ?>
                                <div class="v-author__contact">
                                    <span class="l-page__spacer mrgt15 mrgb5"></span>
                                    <div class="v-author__contact__title align-center"><span><?= _t('shops', 'Контакты') ?>:</span> <a href="#" class="ajax j-shop-view-c-toggler"><?= _t('shops', 'показать контакты') ?></a></div>
                                    <? if( ! empty($shop['phones']) ): ?>
                                        <div class="v-author__contact_items">
                                            <?= _t('users', 'Тел.') ?>
                                            <span class="j-shop-view-c-phones">
                                                <? $i=0; foreach($shop['phones'] as $v): ?>
                                                    <?= $v['m'] ?><? if(++$i < sizeof($shop['phones'])){ ?>, <? } ?>
                                                <? endforeach; ?>
                                            </span>
                                        </div>
                                    <? endif; # phones ?>
                                    <? if( ! empty($shop['contacts'])): ?>
                                        <? foreach (Users::contactsFields($shop['contacts']) as $contact): ?>
                                            <div class="v-author__contact_items">
                                                <?= $contact['title'] ?>
                                                <span class="j-shop-view-c-<?= $contact['key'] ?>">
                                                    <?= tpl::contactMask($contact['value']) ?>
                                                </span>
                                            </div>
                                        <? endforeach; ?>
                                    <? endif; ?>
                                    <? if ( ! empty($shop['social']) && $social ): ?>
                                    <div class="v-author__contact_items">
                                        <div class="sh-shop__list__item_social">
                                            <? foreach($shop['social'] as $v):
                                                if ($v && isset($social[$v['t']])) {
                                                    ?><a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow noreferrer" target="_blank" class="sh-social sh-social_<?= $social[$v['t']]['icon'] ?>"></a><?
                                                }
                                            endforeach; ?>
                                            <div class="clearfix"></div>
                                        </div>
                                    </div>
                                    <? endif; # social ?>
                                </div>
                                <? endif; # has_contacts ?>
                                <? if( ! $is_owner && $has_owner): ?>
                                <div class="v-author__contact_write">
                                    <a class="btn btn-info" href="<?= Shops::urlContact($shop['link']); ?>"><i class="fa fa-envelope white"></i> <?= _t('shops', 'Написать сообщение') ?></a>
                                </div>
                                <? endif; # is_owner and has_owner ?>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? if($has_owner && sizeof($tabs) > 1): ?>
                        <div class="visible-phone">
                            <div class="btn-group sh-menu">
                                <button data-toggle="dropdown" class="btn dropdown-toggle"> <?= $tabs[$tab]['t'] ?>  <i class="fa fa-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <? foreach($tabs as $v) { ?>
                                    <li<? if($v['a']){ ?> class="active"<? } ?>><a href="<?= $v['url'] ?>"><?= $v['t'] ?></a></li>
                                    <? } ?>
                                </ul>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; # has_owner ?>
                    <? endif; # DEVICE_PHONE ?>
                    <? if(DEVICE_DESKTOP_OR_TABLET): ?>
                    <div class="sh-view__info hidden-phone">
                        <h1><?= $shop['title'] ?></h1>
                        <div>
                            <?= $shop['descr']; ?>
                        </div>
                    </div>
                    <? if($has_owner): ?>
                    <ul class="nav nav-tabs hidden-phone mrgt20">
                        <? foreach($tabs as $v) { ?>
                            <li<? if($v['a']){ ?> class="active"<? } ?>><a href="<?= $v['url'] ?>"><?= $v['t'] ?></a></li>
                        <? } ?>
                    </ul>
                    <? endif; # has_owner ?>
                    <? endif; # DEVICE_DESKTOP_OR_TABLET ?>

                    <?= $content ?>

                    <? if ($request_form_visible): ?>
                        <div class="l-center">
                            <div class="l-center__content v-page__content_center">
                                <div class="l-page__spacer hidden-phone mrgt30 mrgb20"></div>
                                <div class="sh-need-owner">
                                    <p><?= _t('shops', 'Если вы являетесь представителем этого магазины вы можете получить доступ к управлению информацией о магазине и размещению объявлений от его имени <a [request_form_link]>подав заявку</a>.', array(
                                        'request_form_link' => ' href="javascript:void(0);" class="ajax" id="j-shop-view-request-form-toggler"',
                                    )) ?></p>
                                    <div class="v-descr_contact hide" id="j-shop-view-request-form-block">
                                        <div class="v-descr_contact_title"><?= _t('shops', 'Укажите ваши контактные данные и мы с вами свяжемся') ?></div>
                                        <div class="v-descr_contact__form">
                                            <form action="" class="j-form">
                                                <? if ( ! User::id()) { ?>
                                                    <input type="text" name="name" class="j-required" placeholder="<?= _te('shops', 'Ваше имя') ?>" maxlength="50" />
                                                    <input type="tel" name="phone" class="j-required" placeholder="<?= _te('shops', 'Ваш телефон') ?>" maxlength="50" />
                                                    <input type="email" name="email" class="j-required" placeholder="<?= _te('shops', 'Ваш email-адрес') ?>" maxlength="100" autocorrect="off" autocapitalize="off" />
                                                <? } ?>
                                                <textarea name="description" class="j-required j-description" placeholder="<?= _te('shops', 'Расскажите как вы связаны с данным магазином') ?>"></textarea>
                                                <small class="help-block grey j-description-maxlength pull-left hidden-phone"></small>
                                                <button type="submit" class="btn pull-right"><i class="fa fa-envelope"></i> <?= _t('shops', 'Отправить заявку') ?></button>
                                                <div class="clearfix"></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    <? endif; ?>

                </div>
            </div>

            <? if(DEVICE_DESKTOP_OR_TABLET): ?>
            <div class="l-right hidden-phone">
                <div class="v-author v-author_shop">
                    <? if($shop['logo']) { ?>
                    <a href="<?= $shop['link'] ?>" class="v-author__avatar">
                        <img src="<?= $shop['logo'] ?>" class="img" alt="" />
                    </a><? } ?>
                    <div class="v-author__info">
                        <? if( ! empty($shop['site'])) { ?><div class="v-author_shop__link"><a href="<?= bff::urlAway($shop['site']) ?>" target="_blank" rel="nofollow noopener" class="ico hide-tail j-away"><i class="fa fa-globe"></i> <span><?= $shop['site'] ?></span></a></div><? } ?>
                        <? if( ! empty($shop['addr_addr']))
                        {
                            if ($shop['addr_map']) {
                                Geo::mapsAPI(false);
                            }
                        ?>
                        <div class="v-author_shop__address rel">
                            <? if($shop['addr_map']){ ?><a href="#" class="ico ajax" id="j-shop-view-map-toggler"><i class="fa fa-map-marker"></i> <span><?= _t('shops', 'Показать на карте') ?></span></a><? } ?>
                            <span class="v-author_shop__address_info"><?= $shop['region_title'].', '.$shop['addr_addr'] ?></span>
                            <? if($shop['addr_map']){ ?>
                            <div id="j-shop-view-map-popup" class="v-map-popup v-map-popup_shop dropdown-block dropdown-block-right box-shadow hide abs">
                                <div id="j-shop-view-map-container" class="v-map-popup__container"></div>
                            </div>
                            <? } ?>
                        </div>
                        <? } ?>
                    </div>

                    <div class="clearfix"></div>
                    <? if($shop['has_contacts']): ?>
                    <div class="v-author__contact">
                        <div class="v-author__contact__title"><span><?= _t('shops', 'Контакты') ?>:</span> <a href="#" class="ajax j-shop-view-c-toggler"><?= _t('shops', 'показать контакты') ?></a></div>
                        <? if( ! empty($shop['phones']) ): ?>
                        <div class="v-author__contact_items">
                            <div class="v-author__contact_title"><?= _t('users', 'Тел.') ?></div>
                            <div class="v-author__contact_content j-shop-view-c-phones">
                                <? foreach($shop['phones'] as $v): ?>
                                    <span class="hide-tail"><?= $v['m'] ?></span>
                                <? endforeach; ?>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; # phones ?>
                        <? if(!empty($shop['contacts'])): ?>
                            <div class="v-author__contact_items">
                                <?php foreach (Users::contactsFields($shop['contacts']) as $contact): ?>
                                    <div class="v-author__contact_title"><?= $contact['title'] ?></div>
                                    <div class="v-author__contact_content j-shop-view-c-<?= $contact['key'] ?>">
                                        <span class="hide-tail"><?= tpl::contactMask($contact['value']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="clearfix"></div>
                            </div>
                        <? endif; ?>

                        <? if ( ! empty($shop['social']) && $social ): ?>
                        <div class="sh-shop__list__item_social">
                            <? foreach($shop['social'] as $v):
                                if ($v && isset($social[$v['t']])) {
                                    ?><a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow noopener" target="_blank" class="sh-social sh-social_<?= $social[$v['t']]['icon'] ?>"></a><?
                                }
                               endforeach; ?>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; # social ?>
                    </div>
                    <? endif; # has_contacts ?>
                    <? if( ! $is_owner && $has_owner): ?>
                        <div class="v-author__contact_write">
                            <a class="btn btn-info" rel="nofollow" href="<?= Shops::urlContact($shop['link']) ?>"><i class="fa fa-envelope white"></i> <?= _t('users', 'Написать сообщение') ?></a>
                        </div>
                    <? endif; # is_owner and has_owner ?>
                </div>
                <div class="v-actions rel">
                    <a href="#" class="ico" id="j-shop-view-send4friend-desktop-link"><i class="fa fa-user"></i> <span><?= _t('shops', 'Поделиться с другом') ?></span></a> <br />
                    <div id="j-shop-view-send4friend-desktop-popup" class="v-send4friend-popup dropdown-block dropdown-block-right box-shadow abs hide">
                        <div class="v-send4friend-popup__form">
                            <form action="" class="form-inline">
                                <input type="text" name="email" class="input-medium j-required" placeholder="<?= _te('', 'E-mail') ?>" />
                                <button type="submit" class="btn j-submit"><?= _t('', 'Отправить') ?></button>
                            </form>
                        </div>
                    </div>
                    <? if ( ! $is_owner): ?>
                    <a href="#" class="ico" id="j-shops-v-claim-desktop-link"><i class="fa fa-fire"></i> <span><?= _t('shops', 'Пожаловаться') ?></span></a>
                    <div id="j-shops-v-claim-desktop-popup" class="v-complaint-popup dropdown-block dropdown-block-right box-shadow abs hide">
                        <div class="v-complaint-popup__form">
                            <?= _t('shops', 'Укажите причины, по которым вы считаете этот магазин некорректным') ?>:
                            <form action="">
                                <? foreach($this->getShopClaimReasons() as $k=>$v):
                                       ?><label class="checkbox"><input type="checkbox" class="j-claim-check" name="reason[]" value="<?= $k ?>" /> <?= $v ?> </label><?
                                   endforeach; ?>
                                <div class="v-complaint-popup__form_other hide j-claim-other">
                                    <?= _t('shops', 'Оставьте ваш комментарий') ?><br />
                                    <textarea name="comment" rows="3" autocapitalize="off"></textarea>
                                </div>
                                <? if( ! User::id() ): ?>
                                    <?php if (Site::captchaCustom('shops-view')): ?>
                                        <?php bff::hook('captcha.custom.view', 'shops-view', __FILE__); ?>
                                    <?php else: ?>
                                        <?= _t('shops', 'Введите результат с картинки') ?><br />
                                        <input type="text" name="captcha" class="input-small required" value="" pattern="[0-9]*" /> <img src="" alt="" class="j-captcha" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&rnd='+Math.random())" />
                                        <br />
                                    <?php endif; ?>
                                <? endif; ?>
                                <button type="submit" class="btn btn-danger j-submit"><?= _t('shops', 'Отправить жалобу') ?></button>
                            </form>
                        </div>
                    </div><br />
                    <? endif; # ! is_owner ?>
                    <? if ($url_promote_visible): ?>
                    <a href="<?= $url_promote ?>" class="ico"><i class="fa fa-arrow-up"></i> <span><?= _t('shops', 'Продвинуть магазин') ?></span></a>
                    <? endif; ?>
                    <? if ( ! empty($share_code)): ?>
                        <span class="l-page__spacer mrgt15 mrgb15"></span>
                        <?= $share_code ?>
                    <? endif; # share_code ?>
                </div>
                <? # Баннер: справа ?>
                <? if ($bannerRight = Banners::view('shops_view_right', array('region'=>$shop['reg3_city']))): ?>
                    <div class="l-banner banner-right">
                        <div class="l-banner__content">
                            <?= $bannerRight ?>
                        </div>
                    </div>
                <? endif; ?>
            </div>
            <div class="clearfix"></div>
            <? endif; # DEVICE_DESKTOP_OR_TABLET ?>

        </div>
    </div>
</div>
<? if ($is_owner): ?>
<div class="l-action-layer fix" id="j-shops-v-owner-panel">
    <div class="l-action-layer__wrapper j-panel-actions">
        <div class="edit">
            <a href="<?= Users::url('my.settings', array('t'=>'shop')) ?>"><i class="fa fa-edit"></i><span><?= _t('shops', 'Изменить информацию') ?></span></a>
        </div>
        <div class="buttons">
            <a href="<?= InternalMail::url('my.messages', array('f'=>InternalMail::FOLDER_SH_SHOP)) ?>" class="btn"><i class="fa fa-envelope"></i> <span class="hidden-phone"> <?= _t('shops', 'Сообщения') ?></span></a>
            <? if ($url_promote_visible) { ?><a href="<?= $url_promote ?>" class="btn btn-success"><?= _t('shops', 'Продвинуть магазин') ?></a><? } ?>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<? endif; # is_owner ?>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jShopsView.init(<?= func::php2js(array(
            'lang'=>array(
                'request' => array(
                    'success' => _t('shops', 'Ваша заявка была успешно отправлена'),
                    'maxlength_symbols_left' => _t('', '[symbols] осталось'),
                    'maxlength_symbols' => _t('', 'знак;знака;знаков'),
                ),
                'sendfriend'=>array(
                    'email' => _t('','E-mail адрес указан некорректно'),
                    'success' => _t('','Сообщение было успешно отправлено'),
                ),
                'claim' => array(
                    'reason_checks' => _t('shops','Укажите причину жалобы'),
                    'reason_other' => _t('shops','Опишите причину подробнее'),
                    'captcha' => _t('','Введите результат с картинки'),
                    'success' => _t('shops','Жалоба успешно принята'),
                ),
            ),
            'id'=>$shop['id'], 'ex'=>$shop['id_ex'].'-'.$shop['id'],
            'claim_other_id'=>Shops::CLAIM_OTHER,
            'addr_map' => ($shop['addr_map']),
            'addr_lat' => $shop['addr_lat'],
            'addr_lon' => $shop['addr_lon'],
            'request_url' => Shops::url('request'),
        )) ?>);
    });
<? js::stop(); ?>
</script>