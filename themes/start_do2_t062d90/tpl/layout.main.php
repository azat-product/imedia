<?php
/**
 * Layout: основной каркас
 * @var $centerblock string содержимое (HTML)
 */
?>
<!DOCTYPE html>
<html xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml" class="no-js">
<head>
  <?= SEO::i()->metaRender(array('content-type'=>true,'csrf-token'=>true)) ?>
  <?= View::template('css'); ?>
</head>
<body data-dbq="<?= bff::database()->statQueryCnt(); ?>">
  <?= View::template('alert'); ?>
  <div class="l-page">
    <!-- Top Banner -->
    <?php if( DEVICE_DESKTOP_OR_TABLET && ($bannerTop = Banners::view('site_top')) ) { ?>
    <div class="l-banner-top">
      <?= $bannerTop; ?>
    </div>
    <?php } ?>
    <!-- Header -->
    <?= View::template('header'); ?>
    <!-- Filter -->
    <?= View::template('filter'); ?>
    <!-- Content -->
    <div class="l-content"> 
      <div class="container">
        <?= $centerblock; ?>
      </div>
    </div>
  </div>

  <?php if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <!-- Back to top -->
    <p class="c-scrolltop" id="j-scrolltop" style="display: none;">
      <a href="#"><span><i class="fa fa-arrow-up"></i></span><?= _t('', 'Наверх'); ?></a>
    </p>
  <?php } ?>

  <!-- Footer -->
  <?= View::template('footer'); ?>
</body>
</html>