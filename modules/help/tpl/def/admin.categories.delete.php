<?php
    /**
     * @var $this Help
     */
    tplAdmin::adminPageSettings(array('title'=>_t('categories', 'Категории / Удаление')));
    $questions = intval($questions);
?>
<script type="text/javascript">
function helpCategoryDelete()
{
    var $catSelect = $('#j-help-category-select');
    if (intval(<?= $questions ?>) > 0 && intval($catSelect.val()) <= 0) {
        $catSelect.focus();
        return false;
    }
    return true;
}
</script>

<form action="" method="post" onsubmit="return helpCategoryDelete();" >
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        <? if ($questions > 0) { ?>
            <p class="text-info"><?= _t('help', 'Прежде чем удалить категорию "[category]", укажите категорию к которой будут<br />относиться вопросы ([num]) относившиеся к удаляемой категории:', array(
                'category' => '<strong>'.$title[LNG].'</strong>',
                'num' => '<a href="'.$this->adminLink('questions&cat='.$id, '', 'html').'">'.$questions.'</a>',
            )) ?> <select name="next" id="j-help-category-select"><?= $categories ?></select></p>
        <? } else { ?>
            <p class="text-info"><?= _t('categories', 'Вы уверены, что хотите удалить категорию "[title]"?', array('title'=>'<strong>'.$title[LNG].'</strong>')); ?></p>
        <? } ?>
    </td>
</tr>
<tr class="footer">
    <td>
        <input type="submit" class="btn btn-danger j-btn-delete" value="<?= _te('', 'Delete') ?>" />
        <input type="button" class="btn j-btn-cancel" value="<?= _te('', 'Cancel') ?>" onclick="return history.back();" />
    </td>
</tr>
</table>
</form>