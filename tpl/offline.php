<?php
/**
 * Сайт отключен
 * @var $offlineReason string причина отключения
 */
?>
<!DOCTYPE html>
<html class="no-js">
<head>
    <?= SEO::i()->metaRender(array('content-type'=>true)); ?>
    <?= View::template('css'); ?>
</head>
<body>
<div id="wrap">
    <!-- BEGIN header -->
    <div id="header">
        <div class="content">
            <div class="container-fluid">
                <div class="l-top row-fluid">
                    <div class="l-top__logo span12">
                        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
                            <!-- for: desktop & tablet -->
                            <div class="hidden-phone">
                                <img src="<?= Site::logoURL('offline') ?>" alt="" />
                            </div>
                        <? } if( DEVICE_PHONE ) { ?>
                            <!-- for: mobile -->
                            <div class="l-top__logo_mobile visible-phone">
                                <img src="<?= Site::logoURL('offline.phone', Site::LOGO_SIZE_SMALL) ?>" alt="" />
                            </div>
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END header -->
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="l-page span12">
                        <h1 class="align-center hidden-phone j-shortpage-title">
                            <?= _t('', 'Сайт временно отключен') ?>
                        </h1>
                        <div class="alert-inline visible-phone">
                            <div class="align-center alert j-shortpage-title">
                                <?= _t('', 'Сайт временно отключен') ?>
                            </div>
                        </div>
                        <div class="l-spacer hidden-phone"></div>
                        <div class="l-shortpage align-center">
                            <?= $offlineReason; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END main content -->
</div>
</body>
</html>