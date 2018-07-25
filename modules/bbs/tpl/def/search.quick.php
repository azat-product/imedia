<?php

/**
 * Быстрый поиск объявлений: список (desktop, tablet)
 * @var $this BBS
 * @var $items array объявления
 */

$lang_photo = _te('bbs', 'фото');

?>
<? foreach($items as $v){ ?>
<a href="<?= $v['link'] ?>" class="f-qsearch__results__item">
    <div class="f-qsearch__results__item__content">
        <div class="f-qsearch__results__item__title">
            <span class="f-qsearch__results__item__title_name"><?= $v['title'] ?></span>
            <? if($v['price_on']) { ?><span class="f-qsearch__results__item__title_price"><?= tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex']) ?></span><? } ?>
        </div>
        <div class="f-qsearch__results__item__img">
            <?
                foreach ($v['img'] as $i):
                    echo '<img src="'.$i.'" title="'.$v['title'].' '.$lang_photo.'" />';
                endforeach;
            ?>
        </div>
        <div class="clearfix"></div>
    </div>
</a>
<? } ?>