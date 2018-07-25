<?php

/**
 * Быстрый поиск объявлений: список (desktop, tablet)
 * @var $this BBS
 * @var $items array объявления
 */

$lang_photo = _te('bbs', 'фото');

?>
<?php foreach($items as $v){ ?>
<a href="<?= $v['link'] ?>" class="l-filter-qsearch-item">
  <span class="l-filter-qsearch-item-title">
    <span class="l-filter-qsearch-item-title-name"><?= $v['title'] ?></span>
    <?php if($v['price_on']) { ?><span class="l-filter-qsearch-item-title-price"><?= tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex']) ?></span><?php } ?>
  </span>
  <span class="l-filter-qsearch-item-img">
    <?php
    foreach ($v['img'] as $i):
      echo '<img src="'.$i.'" title="'.$v['title'].' '.$lang_photo.'" />';
    endforeach;
    ?>
  </span>
</a>
<?php } ?>