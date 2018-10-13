<?php

/**
 * Просмотр объявления: Список похожих объявлений
 * @var $this BBS
 * @var $similar array список объявлений
 */
if (empty($similar)) return '';

?>
<div class="l-blockHeading">
  <div class="l-blockHeading-title"><?= _t('view', 'Другие похожие объявления') ?></div>
</div>

<?php
  foreach ($similar as &$v) {
    echo View::template('search.item.list', array('item'=>&$v), 'bbs');
  } unset($v);
?>