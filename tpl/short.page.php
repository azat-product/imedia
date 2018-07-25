<?php
/**
 * Содержимое страниц сокращенного вида
 * @var $title string заголовок
 * @var $content string содержимое (HTML)
 */
?>
<div class="row-fluid">
    <div class="l-page span12">
        <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
            <h1 class="align-center hidden-phone j-shortpage-title"><?= $title ?></h1>
        <? } ?>
        <? if(DEVICE_PHONE) { ?>
        <div class="alert-inline visible-phone">
             <div class="align-center alert j-shortpage-title"><?= $title ?></div>
        </div>
        <? } ?>
        <div class="l-spacer hidden-phone"></div>
        <div class="l-shortpage">
            <?= $content ?>
        </div>
    </div>
</div>