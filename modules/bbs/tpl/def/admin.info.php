<?php
/** @var $this BBS */
if($this->errors->no()):
    if(empty($message) && ! empty($cronManager)){
        $message = _t('bbs', 'Задача поставлена на обработку и будет выполнена через несколько минут автоматически.');
    }
if( ! empty($message)): ?>
    <div class="alert alert-info">
        <?= $message ?><br />
    </div>
<? endif;
else:
    $errors = $this->errors->get();
?>
    <div class="alert alert-error">
        <?= join('<br >', $errors); ?>
    </div>
<? endif;
