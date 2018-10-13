<?php
/**
 * Layout: для страниц на печать
 * @var $centerblock string содержимое (HTML)
 */
?>
<!DOCTYPE html>
<html class="no-js">
<head>
<?php View::blockStart('head'); ?>
<?= SEO::i()->metaRender(array('content-type'=>true)) ?>
<?= View::template('css'); ?>
<?php View::blockEnd(); ?>
</head>
<body>
<?php View::blockStart('body'); ?>
    <?= View::template('alert'); ?>
	<div class="print-page">
		<!-- BEGIN main content -->
		<div class="l-content">
			<?= $centerblock; ?>
		</div>
		<!-- END main content -->
	</div>
<?php View::blockEnd(); ?>
</body>
</html>