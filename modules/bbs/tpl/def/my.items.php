<?php
    /**
     * Кабинет пользователя: Мои объявления - layout
     * @var $this BBS
     * @var $f array фильтр
     * @var $cats array категории
     * @var $cat_active array текущая категория
     * @var $status array статусы
     * @var $list string список объявлений (HTML)
     * @var $pgn string постраничная навигация (HTML)
     * @var $pgn_pp array варианты кол-ва на страницу
     * @var $total integer всего объявлений
     * @var $device string текущее устройство bff::DEVICE_
     */

    tpl::includeJS(array('history'), true);
    tpl::includeJS('bbs.my', false, 6);
    $f['qq'] = HTML::escape($f['qq']);
?>

<form action="" id="j-my-items-form" class="form-search">
<input type="hidden" name="c" value="<?= $f['c'] ?>" id="j-my-items-cat-value" />
<input type="hidden" name="status" value="<?= $f['status'] ?>" id="j-my-items-status-value" />
<input type="hidden" name="page" value="<?= $f['page'] ?>" />
<input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-items-pp-value" />

<? # Фильтр списка ?>
<div class="u-cabinet__sub-navigation">
    <ul class="nav nav-pills" id="j-my-items-cat">
        <li class="dropdown">
            <a class="dropdown-toggle j-cat-dropdown" data-toggle="dropdown" href="javascript:void(0);">
                <b class="j-cat-title"><?= $cat_active['title'] ?></b>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu j-cat-list">
                <?= $cats ?>
            </ul>
        </li>
        <? if(DEVICE_DESKTOP_OR_TABLET) {
            foreach($status as $k=>$v) {
                ?><li class="u-cabinet__sub-navigation__sort hidden-phone<? if($f['status'] == $k) { ?> active<? } ?> j-status-options"><a href="javascript:void(0);" class="j-status-option" data-value="<?= $k ?>"><span><?= $v['title'] ?></span> <i class="label u-cabinet__sub-navigation__sort__label j-counter"><?= $counters[$k] ?></i></a></li><?
            }
        } ?>
        <li class="u-cabinet__sub-navigation__search pull-right">
            <div class="input-append">
                <input type="text" name="qq" value="<?= $f['qq'] ?>" class="input-medium search-query visible-desktop j-q" />
                <input type="text" name="qq" value="<?= $f['qq'] ?>" class="input-small search-query visible-tablet j-q" />
                <input type="text" name="qq" value="<?= $f['qq'] ?>" class="input-small search-query visible-phone j-q" />
                <button type="button" class="btn j-q-submit"><i class="fa fa-search"></i></button>
            </div>
        </li>
    </ul>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-cabinet__sub-navigation_mobile">
        <div class="u-cabinet__sub-navigation__type visible-phone" id="j-my-items-status-arrows">
            <table>
                <tr>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_left j-left"><a href="javascript:void(0);"><i class="fa fa-chevron-left"></i></a></div></td>
                    <td class="u-cabinet__sub-navigation__type__title j-title"><?= $status[$f['status']]['title'] ?></td>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_right j-right"><a href="javascript:void(0);"><i class="fa fa-chevron-right"></i></a></div></td>
                </tr>
            </table>
        </div>
    </div>
    <? } ?>
    <div class="clearfix"></div>
</div>

<? # Групповые действия с объявлениями ?>
<div class="u-ads__actions hide" id="j-my-items-sel-actions">
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="u-ads__actions_desktop hidden-phone j-my-items-sel-actions-<?= bff::DEVICE_DESKTOP ?> j-my-items-sel-actions-<?= bff::DEVICE_TABLET ?>">
        <span class="u-ads_actions__count">
            <span><?= _t('bbs.my', 'Выбрано'); ?>
                <span class="dropdown">
                    <a href="javascript:void(0);" data-toggle="dropdown" class="ajax ico"><span><b class="j-sel-title"></b></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="javascript:void(0);" class="j-mass-select" data-act="all-page"><?= _t('bbs.my', 'выбрать все на странице'); ?></a></li>
                        <li><a href="javascript:void(0);" class="j-mass-select" data-act="all"><?= _t('bbs.my', 'выбрать все'); ?></a></li>
                        <li><a href="javascript:void(0);" class="j-mass-select" data-act="cancel"><?= _t('bbs.my', 'отменить выбор'); ?></a></li>
                    </ul>
                </span>
            </span>:</span>
        <ul class="unstyled j-sel-actions hide" data-status="1">
            <li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-unpublicate"><?= _t('bbs.my', 'Снять с публикации') ?></a></li>
            <? /* ?><li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-promote"><?= _t('bbs.my', 'Рекламировать') ?></a></li><? */ ?>
            <li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-refresh"><?= _t('bbs.my', 'Продлить') ?></a></li>
            <? if (BBS::svcUpFreePeriod() > 0) { ?><li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-up-free"><?= _t('bbs.my', 'Поднять бесплатно') ?></a></li><? } ?>
        </ul>
        <ul class="unstyled j-sel-actions hide" data-status="2">
            <li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-delete"><?= _t('bbs.my', 'Удалить') ?></a></li>
        </ul>
        <ul class="unstyled j-sel-actions hide" data-status="3">
            <li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-publicate"><?= _t('bbs.my', 'Активировать') ?></a></li>
            <li><a href="javascript:void(0);" class="j-sel-action" data-act="mass-delete"><?= _t('bbs.my', 'Удалить') ?></a></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-ads__actions_mobile visible-phone j-my-items-sel-actions-<?= bff::DEVICE_PHONE ?>">
        <span class="u-ads_actions__count"><span class="j-sel-title"></span>:</span>
        <span class="j-sel-actions hide" data-status="1">
            <a class="btn btn-small j-sel-action" data-act="mass-unpublicate" href="javascript:void(0);"><i class="fa fa-times"></i></a>
            <? /* ?><a class="btn btn-small j-sel-action" data-act="mass-promote" href="javascript:void(0);"><i class="fa fa-gift"></i></a><? */ ?>
            <a class="btn btn-small j-sel-action" data-act="mass-refresh" href="javascript:void(0);"><i class="fa fa-refresh"></i></a>
        </span>
        <span class="j-sel-actions hide" data-status="2">
            <a class="btn btn-small j-sel-action" data-act="mass-delete" href="javascript:void(0);"><i class="fa fa-times"></i></a>
        </span>
        <span class="j-sel-actions hide" data-status="3">
            <a class="btn btn-small j-sel-action" data-act="mass-publicate" href="javascript:void(0);"><i class="fa fa-arrow-up"></i></a>
            <a class="btn btn-small j-sel-action" data-act="mass-delete" href="javascript:void(0);"><i class="fa fa-times"></i></a>
        </span>
        <div class="clearfix"></div>
    </div>
    <? } ?>
</div>

<? if(BBS::limitsPayedEnabled()): ?>
    <div class="alert alert-warning pd15 <?= empty($limitsPayed) ? 'hidden' : '' ?>" id="j-alert-limits-payed">
        <div class="row-fluid">
            <div class="span10 pdt5 pdb5">
                <?= _t('bbs.my', 'Вы достигли лимита активных объявлений'); ?>
            </div>
            <div class="span2">
                <a href="javascript:void(0);" class="btn btn-warning btn-block" id="j-limits-payed-info" data-shop="<?= $shop ?>"><?= _t('', 'Детальнее'); ?></a>
            </div>
        </div>
    </div>
<? endif; ?>
<? if($shop && Shops::abonementEnabled()): ?>
    <div class="alert alert-warning pd15 <?= empty($shopAbonement) ? 'hidden' : '' ?>" id="j-alert-shop-abonement">
        <div class="row-fluid">
            <div class="span10 pdt5 pdb5">
                <?= _t('bbs.my', 'Вы достигли лимита активных объявлений'); ?>
            </div>
            <div class="span2">
                <a href="<?= Users::url('my.settings', array('t' => 'abonement', 'abonement' => 1)) ?>" class="btn btn-warning btn-block"><?= _t('', 'Детальнее'); ?></a>
            </div>
        </div>
    </div>
<? endif; ?>
<? if( ! $this->errors->no()): $errors = $this->errors->get(); ?>
    <div class="alert alert-error pd15">
        <div class="row-fluid">
            <div class="span10 pdt5 pdb5">
                <?= join('<br />', $errors); ?>
            </div>
        </div>
    </div>
<? endif; ?>
<? if( ! empty($messages)): ?>
    <div class="alert alert-info pd15">
        <div class="row-fluid">
            <div class="span10 pdt5 pdb5">
                <?= join('<br />', $messages); ?>
            </div>
        </div>
    </div>
<? endif; ?>

<? # Список объявлений ?>
<div class="u-ads__list sr-page__list" id="j-my-items-list">
    <div class="u-ads__list_desktop sr-page__list_desktop l-table hidden-phone j-my-items-list-<?= bff::DEVICE_DESKTOP ?> j-my-items-list-<?= bff::DEVICE_TABLET ?>">
        <? if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) echo $list; ?>
    </div>
    <div class="u-ads__list_mobile sr-page__list_mobile visible-phone j-my-items-list-<?= bff::DEVICE_PHONE ?>">
        <? if( $device == bff::DEVICE_PHONE ) echo $list; ?>
    </div>
</div>

<? # Постраничная навигация ?>
<div class="u-cabinet__pagination u-fav__pagenation">
    <div class="pull-left" id="j-my-items-pgn">
        <?= $pgn ?>
    </div>
    <ul id="j-my-items-pp" class="u-cabinet__list__pagination__howmany nav nav-pills pull-right hidden-phone<?= ( ! $total ? ' hide' : '' ) ?>">
        <li class="dropdown">
            <a class="dropdown-toggle j-pp-dropdown" data-toggle="dropdown" href="javascript:void(0);">
                <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($pgn_pp as $k=>$v): ?>
                    <li><a href="javascript:void(0);" class="<? if($k == $f['pp']) { ?>active <? } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
                <? endforeach; ?>
            </ul>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
</form>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jMyItems.init(<?= func::php2js(array(
            'lang' => array(
                'sel_selected' => '[items]',
                'sel_items_desktop' => _t('', 'объявление;объявления;объявлений'),
                'sel_items_tablet' => _t('', 'объявление;объявления;объявлений'),
                'sel_items_phone' => _t('', 'об-е;об-я;об-й'),
                'delete_confirm' => _t('bbs.my', 'Удалить объявление?'),
                'delete_confirm_mass' => _t('bbs.my', 'Удалить отмеченные объявления?'),
                'up_auto_on' => _t('bbs.my', 'Включить автоподнятие'),
                'up_auto_off' => _t('bbs.my', 'Настроить автоподнятие'),
            ),
            'status' => $status,
            'total'  => $total,
            'ajax' => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>