<?php
/**
 * Поиск магазинов: форма поиска
 * @var $this Shops
 * @var $f array параметры фильтра
 * @var $catID integer ID текущей категории
 * @var $catData array данные о текущей категории
 * @var $catACTIVE boolean является ли текущая категория выбранной
 * @var $catACTIVE_STEP integer текущий уровень выбора категории: 1,2
 */

tpl::includeJS('shops.search', false, 4);

extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f'); # параметры фильтра => переменные с префиксом f_

?>
<div class="row-fluid">
    <div class="f-msearch rel span12">
    <?
        if (DEVICE_DESKTOP_OR_TABLET) {
            # Подключаем geo-фильтр выбора региона
            echo Geo::i()->filterForm(bff::DEVICE_DESKTOP);
        }
    ?>
    <!-- START main search and filter area -->
    <noindex>
    <form id="j-f-form" action="<?= Shops::url('search', $catData) ?>" method="get" class="form-inline rel">
        <input type="hidden" name="c" value="<?= $f_c ?>"<? if( ! $f_c ) { ?> disabled="disabled"<? } ?> />
        <input type="hidden" name="lt" value="<?= $f_lt ?>" />
        <input type="hidden" name="page" value="<?= $f_page ?>" />
        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
        <!--for: desktop and tablet-->
        <div class="f-msearch_desktop hidden-phone">
            <table width="100%">
                <tr>
                    <td class="category" width="20">
                        <a class="f-msearch_desktop__category btn nowrap" href="#" id="j-f-cat-desktop-link">
                            <span class="title"><?= ( $catACTIVE ? $catData['title'] : _t('shops','Все категории') ) ?></span>
                            <i class="fa fa-caret-down"></i>
                        </a>
                    </td>
                    <td class="input">
                        <input type="text" name="q" id="j-f-query" placeholder="<?= _te('shops','Поиск магазинов...') ?>" autocomplete="off" style="width: 100%" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
                    </td>
                    <td width="70">
                        <button type="submit" class="btn pull-left"><?= _t('shops','Найти') ?></button>
                    </td>
                </tr>
            </table>
            <? /* Фильтр по категории (desktop) */ ?>
            <div id="j-f-cat-desktop-popup" class="f-msearch__categories f-msearch__subcategories dropdown-title-block box-shadow abs hide">
                <div id="j-f-cat-desktop-step1"<? if($catACTIVE_STEP != 1) { ?> class="hide"<? } ?>>
                    <?= $this->catsList('search', bff::DEVICE_DESKTOP, 0); ?>
                </div>
                <div id="j-f-cat-desktop-step2"<? if($catACTIVE_STEP != 2) { ?> class="hide"<? } ?>>
                    <? if($catACTIVE_STEP == 2) echo $this->catsList('search', bff::DEVICE_DESKTOP, $catID); ?>
                </div>
            </div>
        </div>
        <? } if( DEVICE_PHONE ) { ?>
        <!--for: mobile-->
        <div class="f-msearch_mobile visible-phone">
            <?= Geo::i()->filterForm(bff::DEVICE_PHONE); ?>
            <!--STAR select category-->
            <div class="select-ext select-ext-group">
                <div class="select-ext-container " style="width:100%">
                    <a class="select-ext-bnt" href="#" id="j-f-cat-phone-link">
                        <span><?= ( $catACTIVE ? $catData['title'] : _t('shops','Все категории') ) ?></span>
                        <i class="fa fa-caret-down"></i>
                    </a>
                    <div id="j-f-cat-phone-popup" class="select-ext-drop hide"  style="width:99%;">
                        <!-- START categories change as index page -->
                        <div class="f-index__mobile f-index__mobile__mainfilter">
                            <? /* Фильтр по категории (phone) */ ?>
                            <div class="f-msearch__categories">
                                <div id="j-f-cat-phone-step1"<? if($catACTIVE_STEP != 1) { ?> class="hide"<? } ?>>
                                    <?= $this->catsList('search', bff::DEVICE_PHONE, 0); ?>
                                </div>
                                <div id="j-f-cat-phone-step2"<? if($catACTIVE_STEP != 2) { ?> class="hide"<? } ?>>
                                    <? if($catACTIVE_STEP == 2) echo $this->catsList('search', bff::DEVICE_PHONE, $catID); ?>
                                </div>
                            </div>
                        </div>
                        <!-- END categories change as index -->
                    </div>
                </div>
            </div>
            <!--END select category-->
            <div class="input-append span12">
                <input type="text" name="qm" placeholder="<?= _te('shops', 'Поиск магазинов...') ?>" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
                <button type="submit" class="btn"><i class="fa fa-search"></i></button>
            </div>
        </div>
        <? } ?>
    </form>
    </noindex>
    <!-- END main search and filter area -->
    </div>
</div>