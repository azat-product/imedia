<?php
/**
 * Платные лимиты: форма выбора тарифа и способа оплаты
 * @var $this BBS
 * @var $ps array способы оплаты
 * @var $ps_active_key string ключ выбранного способа оплаты по-умолчанию
 * @var $user_balance float текущий баланс пользователя
 * @var $extend boolean продление действующего лимита
 * @var $single array
 * @var $settings array доступные варианты для оплаты
 */

 tpl::includeJS('bbs.limits.payed', false);
 $price = 0;
?>
<div class="row-fluid">
    <div class="l-page u-page span12">
        <div class="i-services">
            <form class="form-horizontal" action="" id="j-limits-paid-form">
                <input type="hidden" name="ps" value="<?= $ps_active_key ?>" class="j-ps-value" />
                <input type="hidden" name="items" value="1" />
                <? if($extend): ?>
                    <h2 class="hide-tail"><?= _t('bbs', 'Продлить лимит объявлений'); ?></h2>
                    <p class=""><?= _t('bbs', 'Срок действия услуги - [term]. Будет продлена до [date].', array(
                            'term' => tpl::declension($term, _t('', 'день;дня;дней')),
                            'date' => tpl::date_format2($expire),
                        )) ?></p>
                <? else: ?>
                    <h2 class="hide-tail"><?= _t('bbs', 'Увеличить лимит объявлений'); ?></h2>
                    <p class=""><?= _t('bbs', 'Вы превысили лимит активных объявлений в данной категории. Чтобы увеличить лимит, выберите пакет дополнительных объявлений.'); ?>
                        <?= $term ? _t('bbs', 'Срок действия услуги - [term].', array('term' => tpl::declension($term, _t('', 'день;дня;дней')))) : '' ?></p>
                <? endif; ?>

                <div class="i-formpage__packages">

                    <? if( ! empty($single)): $price = $single['price']; ?>
                    <div class="i-formpage__package active j-package">
                        <label class="radio">
                            <input type="radio" name="single" value="1" data-price="<?= $single['price'] ?>" checked="checked" />
                        </label>
                        <div class="i-formpage__package__text">
                            <?= _t('bbs', 'Дополнительное объявление в рубрике [title]', array('title' => '<strong>'.$title.'</strong>')); ?>
                        </div>
                        <div class="i-formpage__package__price">
                            <strong><?= $single['price'] ?> <?= Site::currencyDefault() ?></strong>
                        </div>
                    </div>
                    <? endif; ?>

                    <? if( ! empty($settings)): ?>
                    <div class="i-formpage__package j-package">
                        <label class="radio">
                            <input type="radio"  name="single" value="0" <?= empty($single) ? 'checked="checked"' : '' ?> />
                        </label>
                        <div class="i-formpage__package__text">
                            <?= $extend ? _t('bbs', 'Продлить пакет платных объявлений в рубрике <strong>[title]</strong>', array('title' => $title))
                                        : _t('bbs', 'Купить пакет платных объявлений в рубрике <strong>[title]</strong>', array('title' => $title)); ?>
                        </div>
                        <div class="i-formpage__package__select">
                            <? if($extend): $fst = reset($settings); ?>
                                <?= tpl::declension($fst['items'], _t('bbs', 'объявление;объявления;объявлений')) ?>
                                <span class="hidden">
                            <? endif; ?>
                            <select class="j-items">
                                <? $fst = false; foreach($settings as $v): if( ! $fst){ $fst = $v; if(empty($single)) { $price = $v['price']; } } ?>
                                <option value="<?= $v['items'] ?>" data-price="<?= $v['price'] ?>" ><?= tpl::declension($v['items'], _t('bbs', 'объявление;объявления;объявлений')) ?></option>
                                <? endforeach; ?>
                            </select>
                            <? if($extend):?></span><? endif; ?>
                        </div>
                        <div class="i-formpage__package__price">
                            <strong><span class="j-price"><?= $fst['price'] ?></span> <?= Site::currencyDefault() ?></strong>
                        </div>
                    </div>
                    <? endif; ?>

                </div>

                <div class="j-ps-block">
                    <h2 class="hide-tail"><?= _t('bbs', 'Выберите способ оплаты') ?></h2>
                    <div class="control-group">
                        <div class="controls">
                            <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
                                <div class="u-bill__payment_desktop i-services__payment hidden-phone">
                                    <div class="u-bill__payment__methods align-center" style="width: 510px;">
                                        <div class="u-bill__payment__methods__list">
                                            <? foreach($ps as $key=>$v) { ?>
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
                                            <? foreach($ps as $key=>$v) { ?>
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
                        <h3 class="hide-tail"><?= _t('', 'Всего к оплате:'); ?> <b class="j-total"><?= $price ?></b> <?= Site::currencyDefault() ?></h3>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label text-right hidden-phone">
                        <div class="i-formpage__cancel"><button class="btn-link" onclick="history.back(); return false;"><?= _t('', '« Отмена'); ?></button></div>
                    </label>
                    <div class="controls">
                        <input type="submit" class="btn btn-success j-submit" value="<?= _te('', 'Оплатить'); ?>" />
                    </div>
                </div>
            </form>
            <div id="j-limits-paid-form-request" style="display: none;"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
$(function(){
    jBBSLimitsPayed.init(<?= func::php2js(array(
        'lang' => array(
            'svc_select' => _t('bbs', 'Выберите услугу'),
            'ps_select' => _t('bbs', 'Выберите способ оплаты'),
        ),
        'user_balance' => $user_balance,
    )) ?>);
});
<? js::stop(); ?>
</script>