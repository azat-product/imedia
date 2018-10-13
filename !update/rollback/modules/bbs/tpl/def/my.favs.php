<?php
/**
 * Кабинет пользователя: Избранные (объявления)
 * @var $this BBS
 * @var $empty boolean список пустой
 * @var $items array объявления
 * @var $cats array категории
 * @var $cat_active array текущая категория
 * @var $f array параметры фильтра
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 * @var $total integer всего объявлений
 * @var $device string текущее устройство bff::DEVICE_
 */
  if($empty) {
?>
<br><p class="align-center"><?= _t('bbs', 'Список избранных объявлений пустой') ?></p>
<div class="l-page__useful">
    <div class="l-page__useful__item">
        <span><?= _t('bbs', 'Перейти на просмотр <a [link_search]>всех объявлений</a>', array('link_search'=>'href="'.BBS::url('items.search').'"')); ?></span>
    </div>
    <div class="l-page__useful__item">
        <span><?= _t('bbs', 'Перейти на <a [link_home]>главную страницу</a> сайта', array('link_home'=>'href="'.bff::urlBase().'"')); ?></span>
    </div>
</div>
<?php
    return;
  } else {
    tpl::includeJS('history', true);
    tpl::includeJS('bbs.my', false, 6);
  }
?>
<? if ( ! User::id()) { ?>
    <?= tpl::getBreadcrumbs(array(
        array('title'=>_t('bbs', 'Избранные объявления'), 'active'=>true)
    )); ?>
    <h1><?= _t('bbs', 'Избранные объявления') ?></h1>
<? } ?>

<form action="" id="j-my-favs-form">
<input type="hidden" name="c" value="<?= $f['c'] ?>" id="j-my-favs-cat-value" />
<input type="hidden" name="lt" value="<?= $f['lt'] ?>" />
<input type="hidden" name="page" value="<?= $f['page'] ?>" />
<input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-favs-pp-value" />

<? # Фильтр списка ?>
<div class="u-cabinet__sub-navigation">
    <ul class="nav nav-pills pull-left" id="j-my-favs-cat">
        <li class="dropdown">
            <a class="dropdown-toggle j-cat-dropdown" data-toggle="dropdown" href="javascript:void(0);">
                <b class="j-cat-title"><?= $cat_active['title'] ?></b>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($cats as $v) {
                    if( empty($v['sub']) ) {
                        ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?
                    } else {
                        ?><li class="nav-header"><?= $v['title'] ?></li><?
                        foreach($v['sub'] as $vv) {
                            ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?
                        }
                    }
                } ?>
            </ul>
        </li>
    </ul>
    <div class="pull-right">
        <a href="#" class="ajax ajax-ico pseudo-link-ajax u-cabinet__sub-navigation__clear j-cleanup">
            <i class="fa fa-times"></i>
            <span class="hidden-phone"><?= _t('bbs', 'Очистить избранное') ?></span>
            <span class="visible-phone"><?= _t('bbs', 'Очистить') ?></span>
        </a>
    </div>
    <div class="clearfix"></div>
</div>

<? # Список объявлений ?>
<div id="j-my-favs-list">
    <div class="j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
        <? if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) {
            echo $this->searchList(bff::DEVICE_DESKTOP, $f['lt'], $items);
        } ?>
    </div>
    <div class="j-list-<?= bff::DEVICE_PHONE ?>">
        <? if( $device == bff::DEVICE_PHONE ) {
            echo $this->searchList(bff::DEVICE_PHONE, $f['lt'], $items);
        } ?>
    </div>
</div>

<? # Постраничная навигация ?>
<div class="u-cabinet__pagination u-fav__pagenation">
    <div class="pull-left" id="j-my-favs-pgn">
        <?= $pgn ?>
    </div>
    <? if( $total > 15): ?>
    <ul id="j-my-favs-pp" class="u-cabinet__list__pagination__howmany nav nav-pills pull-right hidden-phone">
        <li class="dropdown">
            <a class="dropdown-toggle j-pp-dropdown" data-toggle="dropdown" href="javascript:void(0);">
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
    <? endif; ?>
    <div class="clearfix"></div>
</div>
</form>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jMyFavs.init(<?= func::php2js(array(
            'lang' => array(),
            'ajax' => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>