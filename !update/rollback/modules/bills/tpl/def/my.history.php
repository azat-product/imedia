<?php
/**
 * Кабинет: Счет - история счетов - layout
 * @var $this Bills
 * @var $balance integer|float текущая сумма на счете
 * @var $curr string валюты суммы на счете
 * @var $f array настройки фильтра
 * @var $list string список счетов (HTML)
 * @var $list_empty boolean список пустой
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 */
    tpl::includeJS(array('history'), true);
    tpl::includeJS(array('bills.my'), false);
?>

<? # Заголовок ?>
<div class="u-cabinet__sub-navigation">
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="u-cabinet__sub-navigation_desktop u-cabinet__sub-navigation_bill hidden-phone">
        <div class="pull-left">
             <h3><?= _t('bills', 'История операций') ?></h3>
        </div>
        <div class="pull-right">
            <form class="form-inline" action="">
                <?= _t('bills', 'На вашем счету:') ?> <b><?= $balance.' '.$curr ?></b> &nbsp;
                <a href="<?= Bills::url('my.pay') ?>" class="btn btn-info"><?= _t('bills', 'Пополнить счет') ?></a>
            </form>
        </div>
        <div class="clearfix"></div>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-cabinet__sub-navigation_mobile u-cabinet__sub-navigation_bill visible-phone">
        <div class="pull-left"><h3><?= _t('bills', 'История операций') ?></h3></div>
        <div class="pull-right">
            <form class="form-inline" action="">
                <a href="<?= Bills::url('my.pay') ?>" class="btn btn-info"><?= _t('bills', 'Пополнить счет') ?></a>
            </form>
        </div>
        <div class="clearfix"></div>
    </div>
    <? } ?>
</div>

<form action="" id="j-my-history-form">
<input type="hidden" name="page" value="<?= $f['page'] ?>" />
<input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-history-pp-value" />

<? # Список ?>
<div class="u-bill__list">
    <table>
        <tr>
            <th><?= _t('bills', 'Описание') ?></th>
            <th class="u-bill__list__operation hidden-phone"><?= _t('bills', '№ операции') ?></th>
            <th><?= _t('bills', 'Сумма') ?></th>
        </tr>
        <tbody id="j-my-history-list">
            <?= $list ?>
        </tbody>
    </table>
</div>

<? # Постраничная навигация ?>
<? if ( ! $list_empty ) { ?>
<div class="u-cabinet__pagination">
    <div class="pull-left" id="j-my-history-pgn">
        <?= $pgn ?>
    </div>
    <ul id="j-my-history-pp" class="u-cabinet__list__pagination__howmany nav nav-pills pull-right hidden-phone">
        <li class="dropdown">
            <a class="dropdown-toggle j-pp-dropdown" data-toggle="dropdown" href="#">
                <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($pgn_pp as $k=>$v): ?>
                    <li><a href="#" class="<? if($k == $f['pp']) { ?>active <? } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
                <? endforeach; ?>
            </ul>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
<? } ?>
</form>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBillsMyHistory.init(<?= func::php2js(array(
            'lang' => array(),
            'ajax' => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>