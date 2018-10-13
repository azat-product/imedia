<?php
/**
 * Подключение CSS файлов для админ. панели
 */
tpl::includeCSS('admin-bootstrap');
tpl::includeCSS('admin-responsive');
tpl::includeCSS('admin', true, 2);
bff::hook('admin.css.extra');
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?
foreach (bff::filter('admin.css.includes', tpl::$includesCSS) as $v) {
    ?><link rel="stylesheet" href="<?= $v; ?>" type="text/css" /><?
}