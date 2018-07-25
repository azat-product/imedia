<?php
/**
 * Главная страница
 * @var $this Site
 * @var $titleh1 string заголовок H1
 * @var $centerBlock string центральный блок
 * @var $last string блок последних / премиум объявлений (HTML)
 * @var $lastBlog string блок последнее в блоге (HTML)
 * @var $seotext string SEO-текст
 */
?>
<div class="row-fluid">
    <div class="l-page index-page span12">
<? if(DEVICE_DESKTOP_OR_TABLET) {
    tpl::includeJS('site.index', false, 1);
    ?>
        <? if( ! empty($titleh1) ): ?>
            <h1 class="align-center hidden-phone"><?= $titleh1; ?></h1>
            <div class="l-spacer hidden-phone"></div>
        <? endif; ?>
        <?= $centerBlock ?>
        <? if($banner = Banners::view('site_index_last_before')) { ?>
            <div class="mrgt20 hidden-phone">
                <div class="align-center">
                    <?= $banner; ?>
                </div>
            </div>
        <? } ?>
        <?= $last ?>
        <?= $lastBlog ?>
        <? if($banner = Banners::view('site_index_last_after')) { ?>
            <div class="l-banner hidden-phone mrg20">
                <div class="l-banner__content">
                    <?= $banner; ?>
                </div>
            </div>
        <? } ?>
        <div class="l-info  hidden-phone"><?= $seotext; ?></div>
<? } else { ?>
    <?= $last ?>
<? } ?>
    </div>
</div>