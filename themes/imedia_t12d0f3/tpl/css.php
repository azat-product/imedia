<?= Site::favicon() ?>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<?php
tpl::includeCSS('bootstrap');
tpl::includeCSS('main');
bff::hook('css.extra');
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" media="all" type="text/css" />
<?php
	Minifier::process(tpl::$includesCSS);
	foreach (bff::filter('css.includes', tpl::$includesCSS) as $v) { ?>
	<link rel="stylesheet" href="<?= $v; ?>" type="text/css" />
<?php } ?>
<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->