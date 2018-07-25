<?php
/**
 * Шапка для страниц сокращенного вида
 */
?>
<!-- BEGIN header -->
<div id="header">
    <div class="content">
        <div class="container-fluid">
            <div class="l-top row-fluid">
                <div class="l-top__logo span12">
                    <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
                    <!-- for: desktop & tablet -->
                    <div class="hidden-phone">
                        <a class="logo" href="<?= bff::urlBase() ?>"><img src="<?= Site::logoURL('header.short') ?>" alt="" /></a>
                    </div>
                    <? } if( DEVICE_PHONE ) { ?>
                    <!-- for: mobile -->
                    <div class="l-top__logo_mobile visible-phone">
                        <a class="logo" href="<?= bff::urlBase() ?>"><img src="<?= Site::logoURL('header.short') ?>" alt="" /></a>
                    </div>
                    <? } ?>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- END header -->