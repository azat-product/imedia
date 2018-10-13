<?php

/**
 * Просмотр объявления: Список похожих объявлений
 * @var $this BBS
 * @var $similar array список объявлений
 */
if (empty($similar)) return '';

?>
<div class="v-like">
    <div class="v-like_title"><?= _t('view', 'Другие похожие объявления') ?></div>

    <?= $this->searchList(false, BBS::LIST_TYPE_LIST, $similar); ?>

</div>
