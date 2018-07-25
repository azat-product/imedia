<?php
    /**
     * @var $this Blog
     */
    tplAdmin::adminPageSettings(array('title'=>_t('categories', 'Категории / Удаление')));
    $posts = intval($posts);
?>
<script type="text/javascript">
function blogCategoryDelete()
{
    var $catSelect = $('#j-blog-category-select');
    if (intval(<?= $posts ?>) > 0 && intval($catSelect.val()) <= 0) {
        $catSelect.focus();
        return false;
    }
    return true;
}
</script>

<form action="" method="post" onsubmit="return blogCategoryDelete();" >
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        <? if ($posts > 0) { ?>
            <p class="text-info"><?= _t('help', 'Прежде чем удалить категорию "[category]", укажите категорию к которой будут<br />относиться посты ([num]) относившиеся к удаляемой категории:', array(
                'category' => '<strong>'.$title[LNG].'</strong>',
                'num' => '<a href="'.$this->adminLink('posts&cat='.$id, '', 'html').'">'.$questions.'</a>',
            )) ?> <select name="next" id="j-blog-category-select"><?= $categories ?></select></p>
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