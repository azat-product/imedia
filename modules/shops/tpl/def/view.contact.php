<?php
/**
 * Форма отправки сообщения магазину
 * @var $this Shops
 * @var $link string URL отправки формы
 */
?>
<div class="v-descr_contact pdt15">
    <div class="v-descr_contact__form">
        <form action="<?= Shops::urlContact($link) ?>" id="j-shop-contact-form">
            <?= Users::i()->writeForm('j-shop-contact-form') ?>
        </form>
    </div>
</div>