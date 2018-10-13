<?php
/**
 * Layout: для страниц на печать
 * @var $centerblock string содержимое (HTML)
 */
?>
<!DOCTYPE html>
<html class="no-js">
<head>
<?= SEO::i()->metaRender(array('content-type'=>true)) ?>
<?= View::template('css'); ?>
</head>
<body data-dbq="<?= bff::database()->statQueryCnt(); ?>">
<div id="wrap">
    <!-- BEGIN main content -->
    <div id="main">
        <?= $centerblock; ?>
    </div>
    <!-- END main content -->
</div>
</body>
</html>