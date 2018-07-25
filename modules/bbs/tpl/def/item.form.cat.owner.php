<?php
/**
 * Форма объявления: добавление / редактирование - настройки категории - тип владельца "частное лицо / бизнес"
 * @var $this BBS
 * @var $edit boolean редактирование (true), добавление (false)
 * @var $item array данные об объявлении
 * @var $owner_types array список доступных типов владельца
 * @var $owner_private_form string
 * @var $owner_business boolean
 * @var $owner_business_form string
 */

?>
<div class="control-group<? if ( sizeof($owner_types) == 1 ) { ?> hide<? } ?>">
    <div class="controls"><?
    foreach ($owner_types as $id=>$title): ?>
        <label class="radio inline">
            <input name="owner_type" value="<?= $id ?>" type="radio" <? if($item['owner_type'] == $id) { ?>checked="checked"<? } ?> class="j-required" /><?= $title ?>
        </label><?
    endforeach;
  ?></div>
</div><?