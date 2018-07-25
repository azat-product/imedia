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
<div <?php if ( sizeof($owner_types) == 1 ) { ?> style="display: none;"<?php } ?>>
	<?php foreach ($owner_types as $id=>$title) { ?>
	<div class="radio-inline">
		<label>
			<input name="owner_type" value="<?= $id ?>" type="radio" <?php if($item['owner_type'] == $id) { ?>checked="checked"<?php } ?> class="j-required" /><?= $title ?>
		</label>
	</div>
	<?php } ?>

</div>