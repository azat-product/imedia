<?php
    /**
     * Форма продвижения магазина
     * @var $this Shops
     * @var $shop array данные магазина
     * @var $from string источник перехода на страницу продвижения: 'new', ...
     * @var $svc array данные об услугах
     * @var $svc_id integer ID текущей выбранной услуги
     * @var $svc_prices array настройки цен на услуги
     * @var $ps array способы оплаты
     * @var $user_balance integer текущий баланс пользователя
     * @var $curr string текущая валюта оплаты
     */

    tpl::includeJS('shops.promote', false, 3);
    $promoteAbonement = Shops::abonementEnabled() && ! empty($abonements);
?>

<div class="row-fluid">
    <div class="l-page u-page span12">
        <div class="i-services">
            <form class="form-horizontal" action="" id="j-item-promote-form">
            <input type="hidden" name="ps" value="<?= $ps_active_key ?>" class="j-ps-value" />
            <input type="hidden" name="from" value="<?= HTML::escape($from) ?>" />
            <? if($promoteAbonement): ?>
                <input type="hidden" name="svc" value="<?= $svc_id ?>" />
                <h2 class="hide-tail"><?= _t('shops', '1. Выберите тарифный план'); ?></h2>
                <?= $abonements ?>
            <? else: ?>
            <h2 class="hide-tail">1.
                <? if($from == 'new' && $svc_id) { ?><?= _t('shops', 'Подтверждение выбранной услуги') ?><? } else { ?><?= _t('shops', 'Выберите услугу') ?><? } ?>
            </h2>
            <div class="i-services__list4services bottom">
                <div class="arrow"></div>
                <div class="i-services__list4services__item">
                    <a href="<?= $shop['link'].'?from=promote' ?>" target="_blank"><?= $shop['title'] ?></a>
                </div>
            </div>
            <div class="i-formpage__promotion i-services__promotion j-svc-block">
                <? $i=1; foreach($svc as $v) { if($v['id'] == Shops::SERVICE_ABONEMENT) continue; ?>
                <div class="i-formpage__promotion__item<? if($v['active']){ ?> active<? } ?><? if($i++ != sizeof($svc)) { ?> i-promotion_top<? } else { ?> last<? } ?> j-svc-item" data-price="<?= $v['price'] ?>" data-id="<?= $v['id'] ?>">
                    <label>
                        <div class="i-formpage__promotion__item__title" style="background-color: <?= $v['color'] ?>">
                            <label class="radio">
                                <input type="radio" name="svc"<? if($v['disabled']){ ?> disabled="disabled"<? } ?><? if($v['active']){ ?> checked="checked"<? } ?> autocomplete="off" value="<?= $v['id'] ?>" class="j-check" />
                                <div class="i-formpage__promotion__item__icon"><img src="<?= $v['icon_s'] ?>" alt="" /></div> <?= $v['title_view'] ?>
                                <span class="pull-right">
                                    <?
                                        if( ! $v['price']){ echo _t('shops', 'бесплатно'); } else { ?><strong><?= $v['price'] ?></strong> <?= $curr ?><? }
                                    ?>
                                </span>
                            </label>
                        </div>
                        <div class="i-formpage__promotion__item__descr<? if(! $v['active'] ){ ?> hide<? } ?> j-svc-descr">
                            <?= nl2br($v['description']) ?>
                            <? switch($v['id']) {
                                case Shops::SERVICE_MARK: {
                                    if(($shop['svc'] & $v['id']) && $shop['svc_marked_to'] != Shops::SVC_TERMLESS_DATE) {
                                        ?><br /><br /><?= _t('shops', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($shop['svc_marked_to'], true, true))); ?><?
                                    }
                                } break;
                                case Shops::SERVICE_FIX: {
                                    if(($shop['svc'] & $v['id']) && $shop['svc_fixed_to'] != Shops::SVC_TERMLESS_DATE) {
                                        ?><br /><br /><?= _t('shops', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($shop['svc_fixed_to'], true, true))); ?><?
                                    }
                                } break;
                                default: {
                                    bff::hook('shops.svc.active.shop.promote', $v, array('data'=>&$aData));
                                } break;
                            } ?>
                        </div>
                    </label>
                </div>
                <? } ?>
                <div class="clearfix"></div>
            </div>
            <? endif; ?>

            <div class="j-ps-block hide">
                <h2 class="hide-tail">2. <?= _t('shops', 'Выберите способ оплаты') ?></h2>
                <div class="control-group">
                    <div class="controls">
                        <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
                        <div class="u-bill__payment_desktop i-services__payment hidden-phone">
                            <div class="u-bill__payment__methods align-center" style="width: 510px;">
                                <div class="u-bill__payment__methods__list">
                                    <? foreach($ps as $key=>$v) { if (isset($v['enabled']) && !$v['enabled']) continue; ?>
                                    <div class="u-bill__add__payment__list__item<? if($v['active']) { ?> active<? } ?> j-ps-item j-ps-item-<?= $key ?>" data-key="<?= $key ?>">
                                        <div class="u-bill__add__payment__list__item__ico">
                                            <img src="<?= $v['logo_desktop'] ?>" width="64" alt="" />
                                        </div>
                                        <div class="u-bill__add__payment__list__item__title">
                                            <label class="radio">
                                                <input type="radio" autocomplete="off" value="<?= $key ?>"<? if($v['active']) { ?> checked="checked"<? } ?> class="j-radio" />&nbsp;<?= $v['title'] ?>
                                            </label>
                                        </div>
                                    </div>
                                    <? } ?>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <? } ?>
                        <? if(DEVICE_PHONE) { ?>
                        <div class="u-bill__payment_mobile i-services__payment visible-phone">
                            <div class="u-bill__payment__methods align-center">
                                <div class="u-bill__payment__methods__list">
                                    <? foreach($ps as $key=>$v) { if (isset($v['enabled']) && !$v['enabled']) continue; ?>
                                    <div class="u-bill__add__payment__list__item<? if($v['active']) { ?> active<? } ?> j-ps-item j-ps-item-<?= $key ?>" data-key="<?= $key ?>">
                                        <table>
                                            <tr>
                                                <td class="u-bill__add__payment__list__item__radio"><input type="radio" autocomplete="off" value="<?= $key ?>"<? if($v['active']) { ?> checked="checked"<? } ?> class="j-radio" /></td>
                                                <td class="u-bill__add__payment__list__item__ico"><img src="<?= $v['logo_phone'] ?>" width="32" alt="" /></td>
                                                <td class="u-bill__add__payment__list__item__title"><?= $v['title'] ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <? } ?>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <? } ?>
                    </div>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <h3 class="hide-tail"><?= _t('shops', 'Всего к оплате') ?>: <b class="j-total">0</b> <?= $curr ?></h3>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label text-right hidden-phone">
                    <div class="i-formpage__cancel"><span class="btn-link" onclick="history.back();">&laquo; <?= _t('', 'Отмена') ?></span></div>
                </label>
                <div class="controls">
                    <input type="submit" class="btn btn-success j-submit" value="<?= _te('shops', 'Продолжить') ?>" />
                    <span class="i-formpage__cancel_mobile btn-link cancel" onclick="history.back();"><?= _t('', 'Отмена') ?></span>
                </div>
            </div>
            </form>
            <div id="j-item-promote-form-request" style="display: none;"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jShopsShopPromote.init(<?= func::php2js(array(
            'lang' => array(
                'svc_select' => _t('shops', 'Выберите услугу'),
                'ps_select' => _t('shops', 'Выберите способ оплаты'),
            ),
            'user_balance' => $user_balance,
            'items_total' => 1,
            'svc_prices' => $svc_prices,
            'svc_id' => $svc_id,
            'svc_abon_selected' => $this->input->postget('abonID', TYPE_INT),
            'abonement' => $promoteAbonement,
        )) ?>);
    });
<? js::stop(); ?>
</script>
<?
