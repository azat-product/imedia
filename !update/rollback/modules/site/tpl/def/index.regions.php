<?php
    /**
     * Главная страница: обычный + регионы
     * @var $this Site
     * @var $cats array категории
     * @var $regions array регионы
     */
?>
<div class="row-fluid">
    <div class="index__catlist hidden-phone">
        <div class="index__catlist__left">
            <?php
            $catCnt = 0;
            foreach ($cats as $k => $v) { ?>
                <div class="index__catlist__item i<?= $catCnt % 2 ?>">
                    <div class="index__catlist__item__vert">
                        <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt=""/></a>
                        <div class="title">
                            <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
                            <span class="index__catlist__item__count">(<?= $v['items'] ?>)</span>
                        </div>
                    </div>
                </div>
                <?php if ($catCnt++ % 2) { ?>
                    <div class="clearfix"></div><?php }
            } ?>
        </div>
    </div>
    <div class="index__right">
        <div class="index-banner">
            <p class="index-banner__title"><?= _t('site','Быстро, легко и бесплатно') ?></p>
            <p class="text-center">
                <a href="<?= BBS::url('item.add') ?>" class="btn btn-success btn-lg"><i class="fa fa-plus"></i> <?= _t('site','Подать объявление') ?></a>
            </p>
            <ul>
                <li><?= _t('site','Бесплатно и <strong>без регистрации</strong>') ?></li>
                <li><?= _t('site','До <strong>[photo_ctn] фотографий</strong> в объявлении',array('photo_ctn' => BBS::itemsImagesLimit())) ?></li>
                <?php if (!BBS::formPublicationPeriod()) { ?>
                <li><?= _t('site','Активно до <strong>[expire]</strong>',array('expire' => tpl::declension(config::get('bbs_item_publication_period', 30, TYPE_UINT),_t('','день;дня;дней')))) ?></li>
                <?php } ?>
            </ul>
        </div>

        <?php if (!empty($regions)) { ?>
            <ul class="index-cities index-cities-right">
                <?php $i = 0;
                foreach ($regions as $k => $reg) {
                    if($reg['numlevel'] != Geo::lvlCity) continue;
                    if (++$i > 8) break; ?>
                    <li><a href="<?= $reg['l'] ?>"><strong><?= $reg['title'] ?></strong></a></li>
                    <?php unset($regions[$k]);
                } ?>
            </ul>

            <?php if($catCnt > 8): $catCnt -= 8; $catCnt = ceil($catCnt / 2) * 8; $i = 0; ?>
            <ul class="index-cities index-cities-right">
                <?php foreach ($regions as $k => $reg) {
                    if($reg['numlevel'] != Geo::lvlCity) continue;
                    if(++$i > $catCnt) break; ?>
                    <li><a href="<?= $reg['l'] ?>"><?= $reg['title'] ?></a></li>
                <?php } ?>
            </ul>
            <?php endif; ?>
        <?php } ?>

    </div>
</div>