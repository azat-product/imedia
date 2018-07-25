<?php
/**
 * @var $this BBS
 */
?>
<div id="popupBBSItemInfo" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title"><?= _t('bbs', 'Информация об объявлении №'); ?><?= $id ?></div>
        <div class="ipopup-content" style="width:500px;">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1 field-title right" width="134"><?= _t('users', 'Пользователь:'); ?></td>
                    <td class="row2">
                        <?= ($user_id ? '<a href="#" onclick="return bff.userinfo('.$user_id.');" class="ajax">'.$user['email'].'</a>' : 'Аноним' ); ?>
                    </td>
                </tr>
                <? if( ! empty($shop)) { ?>
                <tr>
                    <td class="row1 field-title right"><?= _t('bbs', 'Магазин:'); ?></td>
                    <td>
                        <a href="<?= $shop['link'] ?>" target="_blank" class="but linkout"></a><a href="#" onclick="return bff.shopInfo(<?= $shop_id ?>);" class="ajax"><?= $shop['title'] ?></a>
                    </td>
                </tr>
                <? } ?>
                <tr>
                    <td class="row1 field-title right" style="vertical-align: top;"><?= _t('bbs', 'Заголовок:'); ?></td>
                    <td class="row2">
                        <a class="linkout but" href="<?= BBS::urlDynamic($link, array('from'=>'adm','mod'=>BBS::moderationUrlKey($id))) ?>" target="_blank"></a><span style="word-break: break-all;"><?= tpl::truncate($title, 70, '...', true) ?></span>
                    </td>
                </tr>
                <?php if (!$cat_id || !isset($cats_path[$cat_id]['price']) || $cats_path[$cat_id]['price']) { ?>
                <tr>
                    <td class="row1 field-title right" style="vertical-align: top;"><?= _t('bbs', 'Цена:'); ?></td>
                    <td class="row2">
                        <?= tpl::itemPrice($price, $price_curr, $price_ex) ?>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <td class="row1 field-title right" style="vertical-align: top;"><?= _t('bbs', 'Описание:'); ?></td>
                    <td class="row2">
                        <span style="word-break: break-all;"><?= tpl::truncate($descr, 170, '...', true) ?></span>
                    </td>
                </tr>
                <? if($imgcnt): ?>
                <tr>
                    <td class="row1 field-title right" style="vertical-align: top;"><?= _t('bbs', 'Фото:'); ?></td>
                    <td class="row2">
                        <? foreach($images as $v): ?>
                        <img src="<?= $img->getURL($v, BBSItemImages::szSmall, false); ?>" alt="" style="margin: 0 5px 5px 0" />
                        <? endforeach; ?>
                    </td>
                </tr>
                <? endif; ?>
                <tr>
                    <td class="row1 field-title right" style="vertical-align: top;"><?= _t('bbs', 'Категория:'); ?></td>
                    <td class="row2">
                        <?php $i=0; $j=sizeof($cats_path);
                        foreach ($cats_path as $v):
                            echo '<a href="'.$this->adminLink('listing&cat='.$v['id']).'">'.$v['title'].'</a>'.(++$i < $j ? ' &raquo; ':'');
                        endforeach; ?>
                    </td>
                </tr>
                <? bff::hook('bbs.admin.item.info', array('data'=>&$aData)) ?>
                <tr>
                    <td colspan="2">
                        <?
                            $aData['is_popup'] = true;
                            echo $this->viewPHP($aData, 'admin.form.status');
                        ?>
                    </td>
                </tr>
            </table>
            <div class="ipopup-content-bottom">
                <ul class="right">
                    <? if($blocked_num>1){ ?><li class="clr-error"><?= _t('bbs', 'блокировок:'); ?> <?= $blocked_num; ?></li><? } ?>
                    <? if($claims_cnt){ ?><li><a href="<?= $this->adminLink('edit&tab=claims&id='.$id); ?>" class="text-error"><?= _t('bbs', 'жалобы'); ?> (<?= $claims_cnt ?>)</a></li><? } ?>
                    <li><span class="post-date" title="<?= _te('', 'Created'); ?>"><?= tpl::date_format3($created); ?></span></li>
                    <li><a href="<?= $this->adminLink('edit&id='.$id); ?>" class="edit_s"> <?= _t('', 'редактировать'); ?> <span style="display:inline;" class="desc">#<?= $id ?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>