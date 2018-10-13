<?php
/**
 * Форма настроек тарифов абонемента: добавление / продление
 * @var $this Shops
 * @var $edit bool true - редактирование текущего тарифа
 * @var $form bool true - отобразить форму выбора
 * @var $user_abonement array параметры текущего тарифа (при редактировании)
 * @var $abonements array параметры всех доступных тарифов (для формы выбора)
 * @var $curr string валюта сайта, например "$"
 */
tpl::includeJS('shops.abonement', false, 1);
?>
<? if ($edit): ?>
    <form class="form-horizontal" action="" id="j-abonement-prolong-form">
    <div class="u-cabinet__settings__block">
        <input type="hidden" name="abonement_id" value="<?= $user_abonement['id'] ?>" />
        <div class="u-cabinet__settings__block__content">
            <div class="control-group">
                <label class="control-label control-label-md"><?= _t('shops', 'Тариф') ?></label>

                <div class="controls controls-noinput">
                    <strong><?= $user_abonement['title'] ?></strong><br>
                    <a href="javascript:void(0);" class="ajax pseudo-link-ajax j-abonement-change-toggle">
                        <small><?= _t('shops', 'изменить') ?></small>
                    </a>
                </div>
            </div>
            <? if (!$user_abonement['price_free'] || $user_abonement['price_free_period']) { ?>
            <div class="control-group" id="j-abonement-user-expire">
                <label class="control-label control-label-md"><?= _t('shops', 'Действует до') ?></label>
                <div class="controls controls-noinput">
                    <span class="mrgr10"><?= date('d.m.Y', strtotime($svc_abonement_expire)) ?></span>
                    <? if (!$user_abonement['one_time'] && !$user_abonement['price_free']) { ?>
                    <label class="checkbox pdt0 inline" id="j-abonement-autoupdate-toggle">
                        <input type="checkbox" class="j-subscribe" value="1" <?= $svc_abonement_auto ? 'checked="checked"' : '' ?> />
                        <small><?= _t('shops', 'Автопродление') ?></small>
                    </label>
                    <br>
                    <a href="javascript:void(0);" class="ajax pseudo-link-ajax j-abonement-prolong-toggle">
                        <small><?= _t('shops', 'продлить') ?></small>
                    </a>
                    <div class="alert alert-warning mrgt10 mrgb0 autoupdate <?= ! $svc_abonement_auto ? 'hide' : '' ?>" id="j-abonement-autoupdate">
                        <?= _t('shops', 'При автопродлении тарифного плана с Вашего счета раз в [month] будет сниматься [amount]', array(
                            'month'  => tpl::declension($svc_abonement_auto_id, _t('','месяц;месяца;месяцев')),
                            'amount' => '<strong>'.$user_abonement['price'][$svc_abonement_auto_id]['pr'].' '.$curr.'</strong>'
                            )) ?>
                    </div>
                    <? } ?>
                </div>
            </div>
            <? if (empty($user_abonement['one_time']) && !$user_abonement['price_free']) { ?>
            <div class="control-group hide" id="j-abonement-prolong">
                <label class="control-label control-label-md"><?= _t('shops', 'Продлить на') ?></label>
                <div class="controls">
                    <select class="span3" name="abonement_period" data-id="<?= $user_abonement['id'] ?>">
                        <? foreach ($user_abonement['price'] as $k => $v) { ?>
                            <option value="<?= $k ?>"><?= $v['m'] ?></option>
                        <? } ?>
                    </select>
                    &nbsp;&nbsp;<?= _t('shops', 'до') ?> <span class="j-abonement-expire"><?= reset($user_abonement['price'])['ex'] ?></span>
                    <div class="mrgt10">
                        <div class="mrgt15">
                            <strong><span class="j-abonement-price"><?= reset($user_abonement['price'])['pr'] ?></span> <?= $curr ?>
                            </strong> <?= _t('shops', 'к оплате') ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mrgt5 j-abonement-change-submit">
                        <?= _t('shops', 'Продлить') ?>
                    </button>
                    <button type="button" class="btn btn-default mrgt5 j-abonement-prolong-toggle">
                        <?= _t('shops', 'Отмена') ?>
                    </button>
                </div>
            </div>
            <? } } ?>
            <div class="control-group">
                <label class="control-label control-label-md"><?= _t('shops', 'Доступно объявлений') ?></label>
                <div class="controls controls-noinput">
                    <? if ($user_abonement['items'] > 0) { ?>
                        <?= _t('shops', '[allowed] из [total]', array(
                            'allowed' => '<strong>'.($user_abonement['items'] - $user_abonement['publicated']).'</strong>',
                            'total' => $user_abonement['items']
                        )); ?>
                    <? } else { ?>
                        <i><?= _t('shops', 'без ограничений') ?></i>
                    <? } ?>
                </div>
            </div>
        </div>
    </div>
    </form>
<? endif; ?>
<? if($form): ?><form class="form-horizontal" action="" id="j-abonement-form"> <? endif; ?>
<div id="j-shops-abonement-change-block" class="<?= $edit ? 'hide' : '' ?>">
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__content">
            <div class="control-group">
                <label class="control-label control-label-md"><?= _t('shops', 'Тариф') ?><br/>
                    <small><?= _t('shops', 'Выберите один из предложенных тарифных планов') ?></small>
                </label>
                <div class="controls controls-md">
                    <div class="i-formpage__tariffs">
                        <? foreach ($abonements as $v) {
                            $one_time = $v['one_time'] && in_array($v['id'], $svc_abonement_one_time);
                            $active = ($v['id'] == $is_default);
                            ?>
                            <label class="i-formpage__tariffs__item" style="cursor: <?= ($one_time?'not-allowed':'pointer') ?>">
                                <input type="radio" name="abonement_id" <?= $one_time ? 'disabled' : '' ?> <?= $active ? 'checked="checked"' : '' ?> value="<?= $v['id'] ?>" data-one_time="<?= $one_time ?>" />
                                <span class="i-formpage__tariffs__item__in">
                                    <span class="i-formpage__tariffs__item__name">
                                        <?= $v['title'] ?>
                                    </span>
                                    <span class="i-formpage__tariffs__item__icon">
                                            <span><img src="<?= $v['img'] ?>" alt=""></span>
                                    </span>
                                    <? if (!empty($v['price_free'])) { ?>
                                        <span class="i-formpage__tariffs__item__price">
                                            <?= _t('shops', 'бесплатно') ?>
                                            <span class="i-formpage__tariffs__item__price__in">
                                                <?= $v['price_free_period'] ? _t('shops', 'сроком на [month]', array('month' => tpl::declension($v['price_free_period'], _t('', 'месяц;месяца;месяцев')))) : _t('shops', 'бессрочно') ?>
                                            </span>
                                        </span>
                                    <? } else { ?>
                                        <span class="i-formpage__tariffs__item__price">
                                        <?= _t('shops', '[price] [curr] за [month]', array('price' => reset($v['price'])['pr'], 'curr' => $curr, 'month' => tpl::declension(key($v['price']), _t('', 'месяц;месяца;месяцев')))) ?>
                                            <? if (count($v['price']) > 1) { ?>
                                                <span class="i-formpage__tariffs__item__price__in">
                                                    <?= _t('shops', 'или [price] [curr] при оплате за [month]', array('price' => end($v['price'])['pr'], 'curr' => $curr, 'month' => tpl::declension(key($v['price']), _t('', 'месяц;месяца;месяцев')))) ?>
                                                </span>
                                            <? } ?>
                                        </span>
                                    <? } ?>
                                    <span class="i-formpage__tariffs__item__benefits">
                                        <span><strong><?= $v['items'] > 0 ? tpl::declension($v['items'], _t('shops', 'объявление;объявления;объявлений')) : '&infin; ' . _t('shops', 'объявлений') ?></strong></span>
                                        <span class="<?= $v['import'] ? '' : 'disabled' ?>"><?= _t('shops', 'Импорт объявлений') ?></span>
                                        <span class="<?= $v['svc_mark'] ? '' : 'disabled' ?>"><?= _t('shops', 'Выделение в каталоге') ?></span>
                                        <span class="<?= $v['svc_fix'] ? '' : 'disabled' ?>"><?= _t('shops', 'Закрепление в каталоге') ?></span>
                                    </span>
                                </span>
                            </label>
                        <? } ?>
                    </div>
                </div>
            </div>
            <? if (!empty($default_price)) {
                $period = $this->input->get('abonPeriod',TYPE_INT);
                $reset = $period ? $default_price[$period] : reset($default_price);
                ?>
                <div class="control-group j-abonement-expire-block <?= $reset['m']?'':'hide' ?>">
                    <label class="control-label"><?= _t('shops', 'Срок') ?><br>
                        <small><?= _t('shops', 'На сколько вы хотите оформить подписку') ?></small>
                    </label>
                    <div class="controls">
                        <select class="span3" name="abonement_period" data-id="<?= $is_default ?>">
                            <? foreach ($default_price as $k => $v) { ?>
                                <option <?= $k == $period ? 'selected' : '' ?> value="<?= $k ?>"><?= isset($v['m'])? $v['m'] : '' ?></option>
                            <? } ?>
                        </select>&nbsp;&nbsp;<?= _t('shops', 'до') ?> <span class="j-abonement-expire"><?= $reset['ex'] ?></span>

                        <div class="mrgt15 j-abonement-price-block <?= $reset['pr']?'':'hide' ?>">
                            <strong><span class="j-abonement-price"><?= $reset['pr'] ?></span> <?= $curr ?>
                            </strong> <?= _t('shops', 'к оплате') ?>
                        </div>
                    </div>
                </div>
            <? } ?>

            <? if ($form): ?>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-success mrgt5 j-abonement-change-submit">
                        <?= _t('shops', 'Сохранить') ?>
                    </button>
                    <button type="button" class="btn btn-default mrgt5 j-abonement-change-toggle">
                        <?= _t('shops', 'Отмена') ?>
                    </button>
                </div>
            </div>
            <? endif; ?>
        </div>
    </div>
</div>
<? if($form): ?></form><? endif; ?>
<script type="text/javascript">
    <? js::start() ?>
    jShopsAbonement.init(<?= func::php2js(array(
        'edit' => $edit,
        'url_submit' => Shops::url('my.abonement'),
        'lang' => array(
            'saved_success' => _t('', 'Настройки успешно сохранены'),
        ),
        'prices' => $prices,
        'user_prices' => $edit ? $user_abonement['price'] : array(),
        'price' => $reset['pr'],
        'abonement' => $this->input->get('abonement', TYPE_UINT),
    )) ?>);
    <? js::stop() ?>
</script>