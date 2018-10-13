<?php

/**
 * Просмотр объявления: Список похожих объявлений
 * @var $this BBS
 * @var $similar array список объявлений
 */
if (empty($similar)) return '';

?>
<div class="v-like">
    <div class="v-like_title"><?= _t('view', 'Другие похожие объявления') ?></div>
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="sr-page__list sr-page__list_desktop v-like__list hidden-phone">
        <?
        $similar_total = sizeof($similar); $i = 1;
        foreach($similar as &$v): ?>
        <div class="sr-page__list__item v-like__list__item">
            <table>
                <tr>
                    <td class="sr-page__list__item_img">
                        <? if( $v['imgs'] ) { ?>
                        <span class="rel inlblk">
                            <a title="<?= $v['title'] ?>" href="<?= $v['link'] ?>" class="thumb stack rel inlblk">
                                <img alt="<?= $v['title'] ?>" src="<?= $v['img_s'] ?>" class="rel br2 zi3 shadow" />
                                <? if( $v['imgs'] > 1 ): ?>
                                <span class="abs border b2 shadow">&nbsp;</span>
                                <span class="abs border r2 shadow">&nbsp;</span>
                                <? endif; ?>
                            </a>
                        </span>
                        <? } else { ?>
                        <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                            <img class="rel br2 zi3 shadow" src="<?= $v['img_s'] ?>" alt="<?= $v['title'] ?>" />
                        </a>
                        <? } ?>
                    </td>
                    <td class="sr-page__list__item_descr v-like__list__item_descr">
                        <h3><? if($v['svc_quick']){ ?><span class="label label-warning quickly"><?= _t('bbs', 'срочно') ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h3>
                        <p><small><?= $v['cat_title'] ?></small></p>
                    </td>
                    <td class="sr-page__list__item_price v-like__list__item_price">
                        <? if($v['price_on']): ?>
                        <strong><?= $v['price'] ?></strong>
                        <small><?= $v['price_mod'] ?></small>
                        <? endif; # price_on ?>
                    </td>
                </tr>
            </table>
        </div>
        <? if($i++ != $similar_total) { ?><div class="spacer"></div><? } ?>
        <? endforeach; unset($v); ?>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE){ ?>
    <div class="sr-page__list sr-page__list_mobile v-like__list visible-phone">
        <? foreach($similar as &$v): ?>
        <div class="sr-page__list__item v-like__list__item">
            <table>
                <tbody>
                    <tr>
                        <td colspan="2" class="sr-page__list__item_descr v-like__list__item_descr"><h5><? if($v['svc_quick']){ ?><span class="label label-warning quickly"><?= _t('bbs', 'срочно') ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>" title="<?= $v['title'] ?>"><?= $v['title'] ?></a></h5></td>
                    </tr>
                    <tr>
                        <td class="sr-page__list__item_date v-like__list__item_date"><?= $v['publicated'] ?></td>
                        <td class="sr-page__list__item_price v-like__list__item_price">
                        <? if($v['price_on']): ?>
                            <strong><?= $v['price'] ?></strong>
                            <small><?= $v['price_mod'] ?></small>
                        <? endif; # price_on ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <? endforeach; unset($v); ?>
    </div>
    <? } ?>
</div>
