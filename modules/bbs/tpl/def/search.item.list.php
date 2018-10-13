<?php
/**
 * Список объявлений: вид строчный список
 * @var $this BBS
 * @var $item array данные объявления
 * @var $opts array доп. параметры
 */
 $lng_quick = _te('bbs', 'срочно');
 $lng_fav_in = _te('bbs', 'Добавить в избранное');
 $lng_fav_out = _te('bbs', 'Удалить из избранного');

 \HTML::attributeAdd($opts['attr'], 'class', 'sr-page__list__item');
 if ($item['svc_marked']) {
     \HTML::attributeAdd($opts['attr'], 'class', 'selected');
 }

 $address = [];
 if ( ! empty($item['city_title'])) {
    $address[] = $item['city_title'];
 }
 if ( ! empty($item['district_title'])) {
    $address[] = $item['district_title'];
 }
 if ( ! empty($opts['showAddr']) && ! empty($item['addr_addr'])) {
    $address[] = $item['addr_addr'];
 }

?>
<div<?= \HTML::attributes($opts['attr']); ?>>
    <table>
        <tr>
            <td class="sr-page__list__item_date hidden-tablet">
                <?php if ($item['publicated_up']): ?>
                <span><span class="ajax j-tooltip" data-toggle="tooltip" data-container="body" data-placement="bottom" data-html="true" data-original-title="<div class='text-left'><?= _te('search', 'Обновлено: [date]', ['date'=>$item['publicated_last']]); ?></div> <div class='text-left'><?= _te('search', 'Размещено: [date]', ['date'=>$item['publicated']]); ?></div>"><?= $item['publicated_last'] ?></span></span>
                <?php else: ?>
                <span><?= $item['publicated'] ?></span>
                <?php endif; ?>
                <?php if($item['fav']) { ?>
                <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                <?php } else { ?>
                <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                <?php } ?>
            </td>
            <td class="sr-page__list__item_img">
                <?php if( $item['imgs'] ) { ?>
                <span class="rel inlblk">
                    <a class="thumb stack rel inlblk" href="<?= $item['link'] ?>" title="<?= $item['title'] ?>">
                        <img class="rel br2 zi3 shadow" src="<?= $item['img_s'] ?>" alt="<?= $item['title'] ?>" />
                        <?php if($item['imgs'] > 1) { ?>
                        <span class="abs border b2 shadow">&nbsp;</span>
                        <span class="abs border r2 shadow">&nbsp;</span>
                        <?php } ?>
                    </a>
                </span>
                <?php } else { ?>
                <a class="thumb stack rel inlblk" href="<?= $item['link'] ?>" title="<?= $item['title'] ?>">
                    <img class="rel br2 zi3 shadow" src="<?= $item['img_s'] ?>" alt="<?= $item['title'] ?>" />
                </a>
                <?php } ?>
            </td>
            <td class="sr-page__list__item_descr">
                <div class="sr-page__list__item_title"><?php if($item['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<?php } ?><a href="<?= $item['link'] ?>"><?= $item['title'] ?></a></div>
                <p><small><?= $item['cat_title'] ?></small></p>
                <?php if( ! empty($item['descr_list'])): ?><small><?= $item['descr_list'] ?></small><?php endif; ?>
                <?php if( ! empty($address)): ?><small><i class="fa fa-map-marker"></i> <?= join(', ', $address); ?></small><?php endif; ?>
            </td>
            <td class="sr-page__list__item_price">
                <?php if($item['price_on']) { ?>
                    <?php if ($item['price']) { ?><strong><?= $item['price'] ?></strong><?php } ?>
                    <?php if ($item['price_mod']) { ?><small><?= $item['price_mod'] ?></small><?php } ?>
                <?php } ?>
                <div class="visible-tablet pull-right">
                    <?php if($item['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <?php } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <?php } ?>
                </div>
            </td>
        </tr>
    </table>
</div>
<div class="spacer"></div>