<?php
/**
 * Главная страница: вид по-умолчанию (список категорий + подкатегорий)
 * @var $this Site
 * @var $cats array категории
 */
?>
<div class="index__catlist hidden-phone">
<?php
$i = 0;
foreach ($cats as $k=>$v): ?>
   <div class="index__catlist__item i<?= $i%2 ?>">
        <?php if ($v['items'] > 0) { ?>
        <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt="" /></a>
        <?php } else { ?>
        <span data-link="<?= $v['l'] ?>" class="img hidden-link"><img src="<?= $v['i'] ?>" alt="" /></span>
        <?php } ?>
        <div class="title">
            <?php if ($v['items'] > 0) { ?>
                <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
            <?php } else { ?>
                <span class="hidden-link" data-link="<?= $v['l'] ?>"><?= $v['t'] ?></span>
            <?php } ?>
            <span class="index__catlist__item__count">(<?= $v['items'] ?>)</span>
        </div>
        <?php if($v['subn']): ?>
            <div class="links">
                <?php $j = 0; foreach($v['sub'] as $vv) { ?>
                    <?php if ($vv['items'] > 0) { ?><a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a><?php } else { ?><span class="hidden-link" data-link="<?= $vv['l'] ?>"><?= $vv['t'] ?></span><?php } ?><?php if($j++ < $v['subv']) echo '; '; } ?>
                <?php if($v['subn'] > $v['subv']){ ?> ...<?php } ?>
            </div>
        <?php endif; ?>
   </div>
   <?php if($i++%2) { ?><div class="clearfix"></div><?php }
endforeach; ?>
<div class="clearfix"></div>
</div>