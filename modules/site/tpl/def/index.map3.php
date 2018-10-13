<?php
    /**
     * Главная страница: вид с картой №3
     * @var $this Site
     * @var $cats array категории
     * @var $regions array регионы
     * @var $map string карта
     */
    $visibleMainCategories = 4; // Кол-во категорий выводимых до ссылки "Все категории"
?>
<div class="index__catlist hidden-phone">
   <?
   $i = 0;
   $bl = 0;
   foreach ($cats as $k=>$v) {
        if ($bl == $visibleMainCategories) { $i = 0; ?>
            <div class="clearfix"></div>
            <div id="index-categories" class="collapse">
        <?php } ?>
        <div class="index__catlist__item i<?= $i % 2 ?>">
            <?php if ($v['items'] > 0) { ?>
            <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt=""/></a>
            <?php } else { ?>
            <span class="img hidden-link" data-link="<?= $v['l'] ?>"><img src="<?= $v['i'] ?>" alt=""/></span>
            <?php } ?>
            <div class="title">
                <?php if ($v['items'] > 0) { ?>
                <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
                <?php } else { ?>
                <span class="hidden-link" data-link="<?= $v['l'] ?>"><?= $v['t'] ?></span>
                <?php } ?>
                <span class="index__catlist__item__count">(<?= $v['items'] ?>)</span>
            </div>
            <?php if ($v['subn']){ ?>
                <div class="links">
                    <?php $j = 0;
                    foreach ($v['sub'] as $vv) { ?>
                        <?php if ($vv['items'] > 0) { ?><a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a><?php } else { ?><span class="hidden-link" data-link="<?= $vv['l'] ?>"><?= $vv['t'] ?></span><?php } ?><?php if ($j++ < $v['subv']) echo '; ';
                    } ?>
                    <?php if ($v['subn'] > $v['subv']) { ?> ...<?php } ?>
                </div>
            <?php } ?>
        </div>
        <?php $bl++; if ($i++ % 2) { ?>
            <div class="clearfix"></div>
        <?php }
    } ?>
    <?php if ($bl > 0) { ?>
        </div>
        <div class="index__more">
            <a href="#index-categories" data-toggle="collapse" class="collapsed"><span class="index__more__show"><?= _t('site','Все категории') ?></span>
                <span class="index__more__hide"><?= _t('site','Свернуть') ?></span></a>
        </div>
    <?php } ?>
</div>

<div class="hidden-phone">
    <div class="index-map index-map__ukr index-map__left">
        <?php if (empty($map)) { ?>
            <div class="index-map__nomap"><?= _t('site','Для данного региона карта еще недоступна.') ?></div>
        <?php } else { ?>
            <?= $map ?>
        <?php } ?>
    </div>
    <div class="index-map__right">
		<?php if (!empty($regions)) { ?>
			<ul class="index-cities index-cities-right">
				<?php $i = 0;
				foreach ($regions as $k => $reg) {
                    if ($reg['numlevel'] != Geo::lvlCity) continue;
					if (++$i > 8) break; ?>
                    <li>
                        <?php if ($reg['items'] > 0) { ?>
                        <a href="<?= $reg['l'] ?>"><strong><?= $reg['title'] ?></strong></a>
                        <?php } else { ?>
                        <span class="hidden-link" data-link="<?= $reg['l'] ?>"><strong><?= $reg['title'] ?></strong></span>
                        <?php } ?>
                    </li>
					<?php unset($regions[$k]);
				} ?>
			</ul>
			<ul class="index-cities index-cities-right">
				<?php $i = 0; foreach ($regions as $k => $reg) {
                    if ($reg['numlevel'] != Geo::lvlCity) continue;
                    if (++$i > 22) break;
                    ?>
                    <li>
                        <?php if ($reg['items'] > 0) { ?>
                        <a href="<?= $reg['l'] ?>"><?= mb_strlen($reg['title']) > 17 ? tpl::truncate($reg['title'], 19, '...', true): $reg['title'] ?></a>
                        <?php } else { ?>
                        <span class="hidden-link" data-link="<?= $reg['l'] ?>"><?= mb_strlen($reg['title']) > 17 ? tpl::truncate($reg['title'], 19, '...', true): $reg['title'] ?></span>
                        <?php } ?>
                    </li>
				<?php } ?>
			</ul>
		<?php } ?>
    </div>
    <div class="clearfix"></div>
</div>