<?= Site::favicon() ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

<?

tpl::includeCSS(['custom-bootstrap','main','custom']);
bff::hook('css.extra');
 ?>
<? if (bff::deviceNoResponsive()) {
    tpl::includeCSS('noresponsive');
} ?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" media="all" type="text/css" />
<?
Minifier::process(tpl::$includesCSS);
foreach (bff::filter('css.includes', tpl::$includesCSS) as $v) {
    ?><link rel="stylesheet" href="<?= $v; ?>" type="text/css" /><?
}