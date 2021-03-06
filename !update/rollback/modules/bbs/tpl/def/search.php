<?php

    /**
     * Поиск объявлений: layout
     * @var $this BBS
     * @var $f array параметры фильтра
     * @var $listTypes array доступные типы списка
     * @var $sortTypes array доступные типы сортировки
     * @var $isMap boolean текущий вид тип списка - карта
     * @var $filterVertical boolean включен вертикальный фильтр
     * @var $cat array параметры текущей категории
     * @var $titleh1 string заголовок H1
     * @var $items array данные объявлений
     * @var $rss array параметры RSS ссылки
     * @var $catsBlock string блок подкатегорий (HTML)
     * @var $premiumBlock string блок премиум-объявлений (HTML)
     * @var $relinkBlock string блок перелинковки (HTML)
     */

    tpl::includeJS(array('history'), true);
    if ($isMap) {
        Geo::mapsAPI(false);
        if (Geo::mapsType() == Geo::MAPS_TYPE_GOOGLE) {
            tpl::includeJS('markerclusterer/markerclusterer', false);
        }
    }

    extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

    $rightBanner = Banners::view('bbs_search_right', array('cat'=>$cat['id'], 'region'=>$f['region'])); # Баннер (справа)
    $rightBlock = $filterVertical || $rightBanner || ! empty($premiumBlock);
    $isMapVertical = false;
    if ($rightBlock && $isMap) {
        $isMapVertical = true;
        $isMap = false;
        config::set('bbs-map-vertical', true);
    }
?>
<?= $catsBlock ?>
<div class="row-fluid">
    <div class="l-page<? if( ! $isMap ) { ?> l-page_right<? } ?> sr-page span12">
        <? if( ! $isMap ) { ?><div class="l-table"><div class="l-table-row"><? } ?>
            <div class="l-main<? if( ! $isMap ) { ?> l-table-cell<? } ?>">
                <div class="l-main__content<?= $rightBlock ? ' l-main__content__short' : '' ?>">
                    <div id="j-bbs-search-list">
                        <ul class="sr-page__main__navigation nav nav-tabs">
                            <?
                            # Типы категорий:
                            if(DEVICE_DESKTOP_OR_TABLET) {
                                foreach($cat['types'] as $k=>$v) {
                                    ?><li class="<? if($k == $f_ct) { ?>active <? } ?>hidden-phone"><a href="javascript:void(0);" class="j-f-cattype-desktop" data="{id:<?= $v['id'] ?>,title:'<?= HTML::escape($v['title'], 'js') ?>'}" data-id="<?= $v['id'] ?>"><b><?= $v['title'] ?></b></a></li><?
                                }
                            }
                            if(DEVICE_PHONE) { ?>
                            <li class="sr-page__navigation__type dropdown rel <? if( sizeof($cat['types']) > 1 ) { ?>visible-phone<? } else { ?>hidden<? } ?>">
                                <a class="dropdown-toggle" id="j-f-cattype-phone-dd-link" data-current="<?= $f_ct ?>" href="javascript:void(0);">
                                    <span class="lnk"><?= $cat['types'][$f_ct]['title'] ?></span> <i class="fa fa-caret-down"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-block box-shadow" id="j-f-cattype-phone-dd">
                                    <?
                                        foreach($cat['types'] as $k=>$v) {
                                        ?><li><a href="javascript:void(0);" class="j-f-cattype-phone" data="{id:<?= $k ?>,title:'<?= HTML::escape($v['title'], 'js') ?>'}"><?= $v['title'] ?></a> </li><?
                                    }
                                    ?>
                                </ul>
                            </li>
                            <? }
                            # Сортировка:
                            if( sizeof($sortTypes) > 1 ) {
                            ?>
                            <li class="sr-page__navigation__sort pull-right dropdown rel">
                                <a class="dropdown-toggle" id="j-f-sort-dd-link" data-current="<?= $f_sort ?>" href="javascript:void(0);">
                                    <span class="hidden-phone"><?= _t('search', 'Сортировка') ?> : </span>
                                    <span class="visible-phone pull-left"><i class="fa fa-refresh"></i>&nbsp;</span>
                                    <span class="lnk"><?= $sortTypes[$f_sort]['t'] ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-block box-shadow" id="j-f-sort-dd">
                                    <? foreach($sortTypes as $k=>$v) { ?><li><a href="javascript:void(0);" class="j-f-sort" data="{key:'<?= $k ?>',title:'<?= HTML::escape($v['t'], 'js') ?>'}"><?= $v['t'] ?></a></li><? } ?>
                                </ul>
                            </li>
                            <? } ?>
                        </ul>
                        <? # Хлебные крошки: ?>
                        <? if(DEVICE_DESKTOP_OR_TABLET) {
                               echo tpl::getBreadcrumbs($cat['crumbs'], false, 'breadcrumb');
                        } ?>
                        <div class="sr-page__result__navigation rel">
                            <div class="sr-page__result__navigation__title pull-left"><h1 class="pull-left"><?= $titleh1 ?></h1></div>
                            <div class="sr-page__list__navigation_view pull-right">
                                <? # Тип списка: ?>
                                <div id="j-f-listtype" class="<?= (empty($items) ? 'hide' : '') ?>">
                                <? foreach($listTypes as $k=>$v) {
                                        ?><a href="javascript:void(0);" data="{id:<?= $k ?>}" data-id="<?= $k ?>" class="j-type<? if($v['a']){ ?> active<? } ?>"><i class="<?= $v['i'] ?>"></i><span class="hidden-phone"><?= $v['t'] ?></span></a><?
                                   } ?>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? if ($filterVertical) { ?>
                            <div class="f-asideFilter__tablet hidden-desktop">
                                <button class="f-asideFilter__toggle collapsed" data-toggle="collapse" data-target="#j-filter-vertical-tablet"><?= _t('filter', 'Фильтр'); ?></button>
                                <div class="f-asideFilter collapse" id="j-filter-vertical-tablet">
                                    <? if(DEVICE_PHONE) { ?><? echo $filterVerticalBlock; $filterVerticalBlock = ''; } ?>
                                </div>
                            </div>
                        <? } ?>
                        <? # Результаты поиска (список объявлений): ?>
                        <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
                        <!-- for: desktop & tablet -->
                        <div class="hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
                            <?= $this->searchList(bff::DEVICE_DESKTOP, $f_lt, $items, array('numStart' => $num_start, 'showBanners' => true)); ?>
                        </div>
                        <? } if(DEVICE_PHONE) { ?>
                        <!-- for: mobile -->
                        <div class="visible-phone j-list-<?= bff::DEVICE_PHONE ?>">
                            <?= $this->searchList(bff::DEVICE_PHONE, $f_lt, $items, array('numStart' => $num_start, 'showBanners' => true)); ?>
                        </div>
                        <? }  ?>
                        <? # RSS: ?>
                        <? if ( ! empty($rss) && ! empty($items)): ?>
                        <div class="l-rssSubscribe">
                            <a href="<?= $rss['link'] ?>" class="ico" target="_blank" rel="nofollow"><i class="fa fa-rss"></i> <span><?= _t('bbs', 'Подписка через RSS на "[title]"', array('title' => $rss['title'])); ?></span></a>
                        </div>
                        <? endif; ?>
                        <? # Постраничная навигация: ?>
                        <div id="j-bbs-search-pgn">
                            <?= $pgn ?>
                        </div>
                    </div>
                </div>
            </div>
            <? if (DEVICE_DESKTOP_OR_TABLET && $rightBlock) {  ?>
            <div class="l-right l-table-cell visible-desktop">
                <div class="l-right__content">
                    <? if ($filterVertical) { ?>
                        <h2><?= _t('filter','Фильтр') ?></h2>
                        <div class="f-asideFilter" id="j-filter-vertical-desktop">
                            <?php echo $filterVerticalBlock; $filterVerticalBlock = ''; ?>
                        </div>
                    <? } ?>
                    <?= $premiumBlock ?>
                    <? if ($rightBanner) { ?>
                    <div class="l-banner banner-right">
                        <div class="l-banner__content">
                            <?= $rightBanner ?>
                        </div>
                    </div>
                    <? } ?>
                </div>
            </div>
            <? } ?>
        <? if( ! $isMap ) { ?></div></div><? } ?>
        <?= $relinkBlock ?>
        <div class="l-info">
            <? if($f['page'] <= 1 && ! empty($cat['seotext'])) echo $cat['seotext'] ?>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBBSSearch.init(<?= func::php2js(array(
            'lang'=>array(
                'range_from' => _t('filter','от'),
                'range_to'   => _t('filter','до'),
                'btn_reset'  => _t('filter','Не важно'),
                'map_toggle_open' => _t('search', 'больше карты'),
                'map_toggle_close' => _t('search', 'меньше карты'),
                'metro_declension' => _t('filter','станция;станции;станций'),
            ),
            'cattype'  => $cat['types'],
            'cattype_ex' => BBS::CATS_TYPES_EX,
            'listtype' => $listTypes,
            'sort'     => $sortTypes,
            'items'    => ( $isMap || $isMapVertical ? $items : array() ),
            'defaultCoords' => Geo::mapDefaultCoords(true),
            'isVertical' => $filterVertical,
            'isMapVertical' => $isMapVertical,
            'ajax'     => (bff::isIndex() ? false : true),
        )) ?>);
    });
<? js::stop(); ?>
</script>
<?

# актуализируем данные формы поиска
# формируемой позже в фаблоне /tpl/filter.php
$this->searchFormData($f);