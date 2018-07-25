<?php
/**
 * Поиск магазинов: список - простой список
 * @var $this Shops
 * @var $items array список магазинов
 */

$socialTypes = Shops::socialLinksTypes();
$lang_shop_items = _t('shops', 'объявление;объявления;объявлений');
$lang_contacts_show = _t('shops', 'Показать контакты');
$listBanner = function($positionNumber) use ($device) {
    if ($device == bff::DEVICE_PHONE) {
        $html = Banners::view('shops_search_list_mobile', array('list_pos' => $positionNumber));
        if ($html) {
            return '<div class="m-banner">'.$html.'</div>';
        }
    } else {
        $html = Banners::view('shops_search_list', array('list_pos' => $positionNumber));
        if ($html) {
            return '<div class="sr-page__list__item">'.$html.'</div> <div class="spacer"></div>';
        }
    }
    return '';
};

?>
<div class="sr-page__list sr-page__list_desktop">
    <?  $n = 1;
        foreach($items as &$v) { ?><?= $listBanner($n++); ?>
        <div class="sr-page__list__item sh-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?> j-shop" data-ex="<?= $v['ex'] ?>">
            <table>
            <tbody>
            <tr>
                <td class="sr-page__list__item_descr">
                    <h3>
                        <a href="<?= $v['link'] ?>" title="<?= $v['title'] ?>"><?= $v['title'] ?></a>
                    </h3>
                    <p><?= tpl::truncate($v['descr'], 170, '...', true) ?></p>

                    <!-- mobile contacts -->
                    <div class="sh-page__list__contacts sh-page__list__contacts__phone">
                        <ul id="j-shops-list-phone-cp-<?= $v['id'] ?>">
                            <? if($v['has_contacts']) { ?>
                            <li>
                                <i class="fa fa-bell"></i> <a href="#" class="ajax j-contacts-ex" data-device="<?= bff::DEVICE_PHONE ?>"><?= $lang_contacts_show ?></a>
                                <div class="hide j-contacts"></div>
                            </li>
                            <? } ?>
                            <? if($v['region_id']){ ?><li><i class="fa fa-map-marker sh-page_icon"></i> <?= $v['region_title'] ?></li><? } ?>
                            <? if( ! empty($v['site'])){ ?><li><i class="fa fa-globe sh-page_icon"></i> <a href="<?= bff::urlAway($v['site']) ?>" rel="nofollow noopener" target="_blank"><?= tpl::truncate(str_replace(array('https://','http://','www.'), '', $v['site']), 26, '...', true) ?></a></li><? } ?>
                        </ul>
                    </div>
                    <a href="<?= $v['link'] ?>" class="hidden-phone"><?= tpl::declension($v['items'], $lang_shop_items) ?> &rsaquo;</a>
                </td>

                <!-- desktop contacts -->
                <td class="sh-page__list__item_right<? if (!bff::deviceNoResponsive()) { ?> hidden-phone<? } ?>">
                    <? if($v['logo']) { ?>
                    <a class="sh-page__list__item_img" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                        <img class="" src="<?= $v['logo'] ?>" alt="<?= $v['title'] ?>" />
                    </a><? } ?>

                    <div class="sh-page__list__contacts">
                        <ul>
                            <li>
                                <? if($v['has_contacts']) { ?>
                                <i class="fa fa-bell"></i> <a href="#" class="ajax j-contacts-ex" data-device="<?= bff::DEVICE_DESKTOP ?>"><?= $lang_contacts_show ?></a>
                                <div class="dropdown-block box-shadow abs hide sh-page__list__dropdown j-contacts"></div>
                            </li>
                            <? } ?>
                            <? if($v['region_id']){ ?><li><i class="fa fa-map-marker sh-page_icon"></i> <?= $v['region_title'] ?></li><? } ?>
                            <? if( ! empty($v['site'])){ ?><li><i class="fa fa-globe sh-page_icon"></i> <a href="<?= bff::urlAway($v['site']) ?>" rel="nofollow noopener" target="_blank"><?= str_replace(array('https://','http://','www.'), '', $v['site']) ?></a></li><? } ?>
                        </ul>
                    </div>

                </td>
            </tr>
            </tbody>
            </table>
        </div>
        <div class="spacer"></div>
    <? } unset($v); ?>
    <?= $last = $listBanner(Banners::LIST_POS_LAST); ?>
    <?= ! $last ? $listBanner($n) : '' ?><?
    if (empty($items)) {
        echo $this->showInlineMessage(_t('shops', 'Список магазинов пустой'));
    } ?>
</div>