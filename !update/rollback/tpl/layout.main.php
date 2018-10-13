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
<?

?>
<?= View::template('css'); ?>
</head>
<body data-dbq="<?= bff::database()->statQueryCnt(); ?>">
<?= View::template('alert'); ?>
<div id="wrap">
    <?= View::template('header'); ?>
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
            <!-- BEGIN filter -->
            <?= View::template('filter'); ?>
            <!-- END filter -->
            <?= $centerblock; ?>
            </div>
        </div>
    </div>
    <!-- END main content -->
    <div id="push"></div>
</div>
<?= View::template('footer'); ?>
</body>
</html>