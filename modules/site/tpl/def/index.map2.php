<?php
    /**
     * Главная страница: вид с картой №2
     * @var $this Site
     * @var $cats array категории
     * @var $map string карта
     */
    $visibleMainCategories = 3; // Кол-во категорий выводимых до ссылки "Все категории"
?>
<div class="index__catlist hidden-phone">
    <?
    $i = 0;
    $j = 0;
    foreach ($cats as $k => $v) { ?>
        <?php if ($j == $visibleMainCategories){ $i = 0; ?>
        <div class="clearfix"></div>
        <div id="index-categories" class="collapse">
        <?php } ?>
        <div class="index__catlist__item index__catlist__item__sm i<?= $i % 3 ?> ">
            <a href="#" class="img"><img src="<?= $v['i'] ?>" alt=""/></a>
            <div class="title"><a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
                <span class="index__catlist__item__count"><?= $v['items'] ?></span>
            </div>
            <?php if ($v['subn']) { ?>
                <ul class="links__vert">
                    <?php foreach ($v['sub'] as $vv) { ?>
                        <li><a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
        <?php $j++;
        if (($i++ % 3) == 2) { ?>
            <div class="clearfix"></div>
        <?php } ?>
    <?php } ?>
    <?php if ($j > 0) { ?>
        </div>
        <div class="index__more">
            <a href="#index-categories" data-toggle="collapse" class="collapsed"><span class="index__more__show"><?= _t('site','Все категории') ?></span>
                <span class="index__more__hide"><?= _t('site','Свернуть') ?></span>
            </a>
        </div>
    <?php } ?>
</div>

<?php if (empty($map)) { ?>
    <div class="index-map__nomap"><?= _t('site','Для данного региона карта еще недоступна.') ?></div>
<?php } else { ?>
    <div class="index-map index-map__russia hidden-phone">
        <?= $map ?>
    </div>
<?php } ?>