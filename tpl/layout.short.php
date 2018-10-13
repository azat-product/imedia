<?php
/**
 * Layout: для страниц сокращенного вида
 * @var $centerblock string содержимое (HTML)
 */
?>
<!DOCTYPE html>
<html xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml" class="no-js">
<head>
<?php View::blockStart('head'); ?>
<?= SEO::i()->metaRender(array('content-type'=>true,'csrf-token'=>true)) ?>
<?= View::template('css'); ?>
<?php View::blockEnd(); ?>
</head>
<body>
<?php View::blockStart('body'); ?>
<?= View::template('alert'); ?>
<div id="wrap">
    <?= View::template('header.short'); ?>
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
                <?= $centerblock; ?>
            </div>
        </div>
    </div>
    <!-- END main content -->
    <div id="push"></div>
</div>
<?= View::template('footer'); ?>
<?php View::blockEnd(); ?>
</body>
</html>