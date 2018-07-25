<?php
/**
 * Главная страница
 * @var $this Site
 * @var $titleh1 string заголовок H1
 * @var $centerBlock string центральный блок
 * @var $last string блок последних / премиум объявлений (HTML)
 * @var $seotext string SEO-текст
 */
?>

<!-- Desktop or Tablet -->
<?php if (DEVICE_DESKTOP_OR_TABLET) { tpl::includeJS('site.index', false, 1); ?>
  <? if ( ! empty($titleh1) ) : ?>
  <div class="index-title">
    <h1 class="align-center hidden-xs"><?= $titleh1; ?></h1>
  </div>
  <? endif; ?>

  <?= $last; // /modules/bbs/tpl/def/index.last.block.php ?>
  <?= $centerBlock ?>

  <?php if($banner = Banners::view('site_index_last_before')) { ?>
  <div class="l-banner-h">
    <?= $banner; ?>
  </div>
  <?php } ?>

  <?= $lastBlog // /modules/blog/tpl/def/index.last.block.php ?>
  <?php if($banner = Banners::view('site_index_last_after')) { ?>
  <div class="l-banner-h">
    <?= $banner; ?>
  </div>
  <?php } ?>
  <div class="l-seoText"><?= $seotext; ?></div>

<?php } ?>

<!-- Mobile -->
<?php if (DEVICE_PHONE) { ?>
  
  <?php if($banner = Banners::view('index_mobile')) { ?>
  <div class="l-banner-m">
    <?= $banner; ?>
  </div>
  <?php } ?>

  <!-- Mobile Home Page Categories -->
  <div class="mobile-container">
    <div id="j-f-cat-phone-index-step1">
      <?= BBS::i()->catsList('index', bff::DEVICE_PHONE, 0); ?>
    </div>
    <div id="j-f-cat-phone-index-step2" class="hide"></div>
  </div>

  <?= $last ?>
<?php } ?>