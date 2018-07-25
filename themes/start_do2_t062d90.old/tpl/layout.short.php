<?php
/**
 * Layout: для страниц сокращенного вида
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
    <!-- Header -->
    <?= View::template('header.short'); ?>
    <!-- BEGIN main content -->
    <div class="l-content">
      <div class="container">
        <?= $centerblock; ?>
      </div>
    </div>
  </div>
  <!-- Footer -->
  <?= View::template('footer'); ?>
</body>
</html>