<?php
/**
 * Текст ошибки 404 (страница не найдена)
 */
?>
<div class="l-headerShort-heading">
  <h1 class="l-headerShort-heading-title j-shortpage-title"><?= _t('error', 'Страница не найдена. Ошибка 404.') ?></h1>
</div>
<div class="l-content">
  <div class="container">
    <p>
      <span class="visible-xs"><b><?= _t('error', 'Страница не найдена. Ошибка 404.') ?></b></span>
      <?= _t('error', 'Страницы, на которую вы попытались попасть не существует.') ?>
    </p>
    <p><?= _t('error', 'Попробуйте её найти вернувшись на <a [home]>главную страницу</a>.', array('home'=>'href="'.bff::urlBase().'"')) ?></p>
    <p><?= _t('error', 'Если вы уверены в том, что эта странца здесь должна быть, то <a [contact]>напишите нам</a>, пожалуйста.', array('contact'=>'href="'.Contacts::url('form').'"')) ?></p>
    <form action="<?= BBS::url('items.search') ?>" class="form-inline">
      <div class="form-group">
        <input type="text" class="form-control" name="q" maxlength="80">
      </div>
      <button type="submit" class="btn btn-default"><?= _t('error', 'Найти') ?></button>
    </form>
  </div>
</div>