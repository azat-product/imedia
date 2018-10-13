<?php
/**
 * Карта сайта
 * @var $this Site
 * @var $breadcrumb string название хлебной крошки или пустая строка
 * @var $titleh1 string заголовок H1 или пустая строка
 * @var $cats array категории
 * @var $seotext string SEO-текст
 */
?>
<div class="row-fluid">
    <div class="l-page span12">
            <?php # Хлебные крошки: ?>
            <?= tpl::getBreadcrumbs(array(
                array('title'=>(!empty($breadcrumb) ? $breadcrumb : _t('', 'Карта сайта')), 'link'=>'#', 'active'=>true)
            )); ?>
            <h1><?= (!empty($titleh1) ? $titleh1 : _t('', 'Карта сайта')) ?></h1>
            <div class="l-sitemap row-fluid">

                <? foreach($cats as &$row) { ?>
                    <div class="span4">
                        <? foreach($row as &$c) { ?>
                            <ul>
                                <li>
                                    <img src="<?= $c['icon'] ?>" alt="<?= $c['title'] ?>" />
                                    <?php if ($c['items'] > 0) { ?>
                                        <a href="<?= $c['link'] ?>"><?= $c['title'] ?></a>
                                    <?php } else { ?>
                                        <span class="hidden-link" data-link="<?= $c['link'] ?>"><?= $c['title'] ?></span>
                                    <?php } ?>
                                    <ul>
                                        <? foreach($c['subs'] as &$cc) { ?>
                                            <li>
                                                <?php if ($cc['items'] > 0) { ?>
                                                <a href="<?= $cc['link'] ?>"><?= $cc['title'] ?></a>
                                                <?php } else { ?>
                                                    <span class="hidden-link" data-link="<?= $cc['link'] ?>"><?= $cc['title'] ?></span>
                                                <?php } ?>
                                            </li>
                                        <? } unset($cc); ?>
                                    </ul>
                                </li>
                            </ul>
                        <? } unset($c); ?>
                    </div>
                <? } unset($row); ?>
                <div class="clearfix"></div>
            </div>
        <div class="l-info">
            <?= $seotext ?>
        </div>
    </div>
</div>