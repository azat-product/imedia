<?php
    View::setLayout('short');
    config::set('tpl.footer.hide', true);
?>
<div class="l-headerShort-heading">
  <h1 class="l-headerShort-heading-title"><?= _t('', 'Сайт временно отключен') ?></h1>
</div>
<div class="text-center">
  <?= $offlineReason; ?>
</div>