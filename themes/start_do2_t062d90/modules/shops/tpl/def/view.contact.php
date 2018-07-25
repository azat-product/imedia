<?php
/**
 * Форма отправки сообщения магазину
 * @var $this Shops
 * @var $link string URL отправки формы
 */
?>
<form action="<?= Shops::urlContact($link) ?>" id="j-shop-contact-form">
	<?= Users::i()->writeForm('j-shop-contact-form') ?>
</form>