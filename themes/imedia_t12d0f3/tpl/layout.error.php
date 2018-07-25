  <?php
/**
 * Layout: для страниц ошибок
 * @var $title string заголовок
 * @var $centerblock string содержимое (HTML)
 */
?>
<!DOCTYPE html>
<html class="no-js">
<head>
  <?= SEO::i()->metaRender(array('content-type'=>true, 'mtitle'=>$title)) ?>
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