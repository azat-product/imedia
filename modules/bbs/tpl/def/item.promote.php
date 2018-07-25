<?php
    /**
     * Форма продвижения объявления
     * @var $this BBS
     * @var $item array данные объявления
     * @var $from string источник перехода на страницу продвижения
     * @var $svc array данные об услугах
     * @var $svc_id integer ID текущей выбранной услуги
     * @var $svc_prices array настройки цен на услуги
     * @var $ps array способы оплаты
     * @var $user_balance integer текущий баланс пользователя
     * @var $curr string текущая валюта оплаты
     */

    tpl::includeJS('bbs.promote', false, 6);
    $lang_free = _t('bbs', 'бесплатно');
?>

<div class="row-fluid">
    <div class="l-page u-page span12">
        <div class="i-services">
            <form class="form-horizontal" action="" id="j-item-promote-form">
            <input type="hidden" name="ps" value="<?= $ps_active_key ?>" class="j-ps-value" />
            <input type="hidden" name="from" value="<?= HTML::escape($from) ?>" />
            <h2 class="hide-tail">1.
                <? if($from == 'new' && $svc_id) { ?><?= _t('bbs', 'Подтверждение выбранной услуги') ?><? } else { ?><?= _t('bbs', 'Выберите услугу') ?><? } ?>
            </h2>
            <div class="i-services__list4services bottom">
                <div class="arrow"></div>
                <div class="i-services__list4services__item">
                    <a href="<?= $item['link'].'?from=promote' ?>" target="_blank"><?= $item['title'] ?></a>
                </div>
            </div>
            <div class="i-formpage__promotion i-services__promotion j-svc-block">
                <? $i=1; foreach($svc as $v) { ?>
                <div class="i-formpage__promotion__item<? if($v['active']){ ?> active<? } ?><? if($i++ != sizeof($svc)) { ?> i-promotion_top<? } else { ?> last<? } ?> j-svc-item" data-price="<?= $v['price'] ?>" data-id="<?= $v['id'] ?>">
                    <label>
                        <div class="i-formpage__promotion__item__title" style="background-color: <?= $v['color'] ?>">
                            <label class="radio">
                                <input type="radio" name="svc"<? if($v['disabled']){ ?> disabled="disabled"<? } ?><? if($v['active']){ ?> checked="checked"<? } ?> autocomplete="off" value="<?= $v['id'] ?>" class="j-check" />
                                <div class="i-formpage__promotion__item__icon"><img src="<?= $v['icon_s'] ?>" alt="" /></div> <?= $v['title_view'] ?>
                                <? if($v['id'] == BBS::SERVICE_PRESS && $item['svc_press_date_last'] != '0000-00-00'): ?> <span class="grey hidden-phone"><?= _t('bbs', '(предыдущая публикация [date])', array('date' => tpl::date_format2($item['svc_press_date_last'], true))) ?></span><? endif; ?>
                                <span class="pull-right">
                                    <?
                                        if ( $v['id'] == BBS::SERVICE_UP && $item['svc_up_activate'] > 0 ) {
                                            echo _t('bbs', 'оплачено: <b>[up]</b>', array('up'=>$item['svc_up_activate']));
                                        } else if ( $v['id'] == BBS::SERVICE_PRESS && $item['svc_press_status'] > 0 ) {
                                            if($item['svc_press_status'] == BBS::PRESS_STATUS_PAYED) {
                                                ?><b><span class="hidden-phone"><?= _t('bbs', 'ожидает публикации'); ?></span><span class="visible-phone"><?= _t('bbs', 'опл.') ?></span></b><?
                                            } else if($item['svc_press_status'] == BBS::PRESS_STATUS_PUBLICATED) {
                                                ?><span class="hidden-phone"><?= _t('bbs', 'опубликовано') ?>&nbsp;</span><b><?= tpl::date_format2($item['svc_press_date'], true) ?></b><?
                                            }
                                        } else {
                                            if( ! $v['price']){ echo $lang_free; } else { ?><strong><?= tpl::currency($v['price']) ?></strong> <?= $curr ?><? }
                                        }
                                    ?>
                                </span>
                            </label>
                        </div>
                        <div class="i-formpage__promotion__item__descr<? if(! $v['active'] ){ ?> hide<? } ?> j-svc-descr">
                            <? if ($v['id'] == BBS::SERVICE_FIX && ! empty($v['period_type']) && $v['period_type'] == BBS::SVC_FIX_PER_DAY) {
                                $days = $this->input->get('fix_days', TYPE_UINT); if($days <= 0) $days = config::sysAdmin('bbs.services.fix.days.default', 1, TYPE_UINT); ?>
                                <?= _t('bbs', 'Закрепить на [input] день', array('input'=>
                                    '<input class="input-mini text-center" value="'.$days.'" type="number" name="fix_days" min="1" max="999" />'
                                ))?><div class="l-spacer"></div>
                            <? } ?>
                            <?= $v['id'] == BBS::SERVICE_UP ? $svc_autoup_form : '' ?>
                            <?= nl2br($v['description']) ?>
                            <? switch($v['id']) {
                                case BBS::SERVICE_MARK: {
                                    if($item['svc'] & $v['id']) {
                                        ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_marked_to'], true, true))); ?><?
                                    }
                                } break;
                                case BBS::SERVICE_FIX: {
                                    if($item['svc'] & $v['id']) {
                                        ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_fixed_to'], true, true))); ?><?
                                    }
                                } break;
                                case BBS::SERVICE_QUICK: {
                                    if($item['svc'] & $v['id']) {
                                        ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_quick_to'], true, true))); ?><?
                                    }
                                } break;
                                case BBS::SERVICE_PREMIUM: {
                                    if($item['svc'] & $v['id']) {
                                        ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_premium_to'], true, true))); ?><?
                                    }
                                } break;
                                default: {
                                    bff::hook('bbs.svc.active.item.promote', $v, array('data'=>&$aData));
                                } break;
                            } ?>
                        </div>
                    </label>
                </div>
                <? } ?>
                <div class="clearfix"></div>
            </div>

            <div class="j-ps-block hide">
                <h2 class="hide-tail">2. <?= _t('bbs', 'Выберите способ оплаты') ?></h2>
                <div class="control-group">
                    <div class="controls">
                        <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
                        <div class="u-bill__payment_desktop i-services__payment hidden-phone">
                            <div class="u-bill__payment__methods align-center" style="width: 510px;">
                                <div class="u-bill__payment__methods__list">
                                    <? foreach($ps as $key=>$v) {if (isset($v['enabled']) && !$v['enabled']) continue; ?>
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
                    <h3 class="hide-tail"><?= _t('bbs', 'Всего к оплате') ?>: <b class="j-total">0</b> <?= $curr ?></h3>
                </div>
            </div>
            <div class="control-group hidden-phone">
                <label class="control-label text-right">
                    <div class="i-formpage__cancel"><span class="btn-link" onclick="history.back();">&laquo; <?= _t('', 'Отмена') ?></span></div>
                </label>
                <div class="controls">
                    <input type="submit" class="btn btn-success j-submit" value="<?= _te('bbs', 'Продолжить') ?>" />
                </div>
            </div>
            <div class="control-group visible-phone">
                <div class="controls">
                    <input type="submit" class="btn btn-success j-submit" value="<?= _te('bbs', 'Продолжить') ?>" />
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
        jBBSItemPromote.init(<?= func::php2js(array(
            'lang' => array(
                'svc_select' => _t('bbs', 'Выберите услугу'),
                'ps_select' => _t('bbs', 'Выберите способ оплаты'),
            ),
            'user_balance' => $user_balance,
            'items_total' => 1,
            'svc_prices' => $svc_prices,
            'svc_id' => $svc_id,
            'svc_fix_id' => BBS::SERVICE_FIX,
            )) ?>);
    });
<? js::stop(); ?>
</script>
<?
