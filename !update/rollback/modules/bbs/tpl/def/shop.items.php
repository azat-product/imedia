<?php
    /**
     * Список объявлений магазина: layout
     * @var $this BBS
     * @var $empty boolean список пустой
     * @var $items array объявления
     * @var $cats array категории фильтра
     * @var $cat_active array текущая категория
     * @var $f array фильтр
     * @var $pgn string постраничная навигация (HTML)
     * @var $device string текущее устройство bff::DEVICE_
     */
    if ($empty) {
        echo $this->showInlineMessage(_t('bbs', 'Список объявлений магазина пустой'));
        return;
    }
    tpl::includeJS(array('history'), true);
    tpl::includeJS('bbs.shop', false, 2);
?>

<form action="" id="j-shop-view-items-list">
<input type="hidden" name="c" value="<?= $f['c'] ?>" class="j-cat-value" />
<input type="hidden" name="page" value="<?= $f['page'] ?>" />
<div class="sh-view__navigation rel mrgt10">
    <ul class="nav nav-pills pull-left j-cat">
        <li class="dropdown">
            <a class="dropdown-toggle j-cat-dropdown" data-toggle="dropdown" href="#">
                <b class="j-cat-title"><?= $cat_active['title'] ?></b>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($cats as $v):
                    if( empty($v['sub']) ) {
                        ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?
                    } else {
                        ?><li class="nav-header"><?= $v['title'] ?></li><?
                        foreach($v['sub'] as $vv) {
                            ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?
                        }
                    }
                   endforeach; ?>
            </ul>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>

<? # Список объявлений ?>
<div class="j-list">
    <div class="sr-page__list sr-page__list_desktop hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
        <? if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) {
            echo $this->searchList(bff::DEVICE_DESKTOP, $f['lt'], $items);
        } ?>
    </div>
    <div class="sr-page__list sr-page__list_mobile visible-phone j-list-<?= bff::DEVICE_PHONE ?>">
        <? if( $device == bff::DEVICE_PHONE ) {
            echo $this->searchList(bff::DEVICE_PHONE, $f['lt'], $items);
        } ?>
    </div>
</div>
</form>

<? # Постраничная навигация ?>
<div id="j-shop-view-items-pgn">
    <?= $pgn ?>
</div>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBBSShopItems.init(<?= func::php2js(array(
            'lang' => array(),
            'ajax' => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>