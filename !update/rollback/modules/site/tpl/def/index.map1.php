<?php
    /**
     * Главная страница: вид с картой №1
     * @var $this Site
     * @var $cats array категории
     * @var $regions array регионы
     * @var $map string карта
     */
?>
<div class="index__catlist hidden-phone">
    <div class="row-fluid">
        <div class="index__catlist__left">
            <div class="index__heading">
                <h2><?= _t('site','Рубрики объявлений') ?></h2>
            </div>
            <?php
            $catCnt = 0;
            foreach ($cats as $k => $v): ?>
                <div class="index__catlist__item i<?= $catCnt % 2 ?>">
                    <div class="index__catlist__item__vert">
                        <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt=""/></a>
                        <div class="title">
                            <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
                            <span class="index__catlist__item__count">(<?= $v['items'] ?>)</span>
                        </div>
                    </div>
                </div>
                <? if ($catCnt++ % 2) { ?>
                    <div class="clearfix"></div>
                <? }
            endforeach; ?>
        </div>
        <div class="index__right">
            <!-- Map -->
            <div class="index__heading">
                <h2><?= _t('site','Объявления на карте') ?></h2>
            </div>
            <? if (empty($map)) { ?>
                <div class="index-map__nomap"><?= _t('site','Для данного региона карта еще недоступна.') ?></div>
            <? } else { ?>
                <div class="index-map index-map__ukr hidden-phone mrgt15">
                    <?= $map ?>
                </div>
            <? } ?>

            <? if (!empty($regions)) { ?>
                <ul class="index-cities index-cities-right">
                    <? $i = 0;
                    foreach ($regions as $k => $reg) {
                        if($reg['numlevel'] != Geo::lvlCity) continue;
                        if (++$i > 8) break; ?>
                        <li><a href="<?= $reg['l'] ?>"><strong><?= $reg['title'] ?></strong></a></li>
                        <? unset($regions[$k]);
                    } ?>
                </ul>

                <? if($catCnt > 8): $catCnt -= 8; $catCnt = ceil($catCnt / 2) * 8; $i = 0; ?>
                <ul class="index-cities index-cities-right">
                    <? foreach ($regions as $k => $reg) {
                        if($reg['numlevel'] != Geo::lvlCity) continue;
                        if(++$i > $catCnt) break;
                        ?>
                        <li><a href="<?= $reg['l'] ?>"><?= $reg['title'] ?></a></li>
                    <? } ?>
                </ul>
                <? endif; ?>
            <? } ?>
        </div>
    </div>
</div>