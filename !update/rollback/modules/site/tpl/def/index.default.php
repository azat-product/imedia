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
        <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt="" /></a>
        <div class="title">
            <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
      
                       
        </div>
        <?php if($v['subn']): ?>
            <div class="links">
                <?php $j = 0; foreach($v['sub'] as $vv) { ?><a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a><?php if($j++ < $v['subv']) echo '; '; } ?>
                <?php if($v['subn'] > $v['subv']){ ?> ...<?php } ?>
            </div>
        <?php endif; ?>
   </div>
   <?php if($i++%2) { ?><div class="clearfix"></div><?php }
endforeach; ?>
<div class="clearfix"></div>
</div>
