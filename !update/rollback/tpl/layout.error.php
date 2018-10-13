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
<div id="wrap">
    <?= View::template('header.short'); ?>
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="l-page span12">
                        <?= $centerblock; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END main content -->
    <div id="push"></div>
</div>
<?= View::template('footer'); ?>
</body>
</html>