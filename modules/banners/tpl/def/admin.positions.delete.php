<?php ?>
<form method="post" action="">
<input type="hidden" name="del" id="banners-del-flag" value="0" />
<?php if( $banners > 0 ) { ?>
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        <?= _t('banners', 'Прежде чем удалить позицию [title], укажите позицию к которой<br />будут относиться все баннеры ([link]) относившиеся к удаляемой позиции:', array(
            'title' => '<b>'.$title.'</b>',
            'link' => '<b><a href="'.$this->adminLink('listing&pos='.$id).'" target="_blank">'.$banners.'</a></b>'
        )); ?>
        <br />
        <select style="margin-top: 10px;" name="next">
            <option value="0"><?= _t('','Выбрать') ?></option>
            <?php foreach($positions as $v) { ?>
                <option value="<?= $v['id'] ?>"><?= $v['title'].'&nbsp;('.$v['sizes'].')' ?></option>
            <?php } ?>
        </select>
    </td>
</tr>
<tr class="footer">
    <td>
        <input type="submit" class="btn btn-danger button delete" value="<?= _te('banners', 'Удалить с заменой'); ?>" />
        <?php if(FORDEV){ ?><input type="button" class="btn btn-danger button delete" value="<?= _te('banners', 'Удалить позицию и баннеры'); ?>" onclick="$('#banners-del-flag').val(1);" /><?php } ?>
        <input type="button" class="btn button cancel" onclick="history.back();" value="<?= _te('', 'Cancel') ?>" />
    </td>
</tr>
</table>
<?php } else { ?>
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        <?= _t('banners', 'Вы действительно хотите удалить позицию [title]?', array('title' => '<b>'.$title.'</b>')); ?>
    </td>
</tr>
<tr class="footer">
    <td>
        <input type="submit" class="btn btn-danger button delete" value="<?= _te('', 'Delete') ?>" />
        <input type="button" class="btn button cancel" onclick="history.back();" value="<?= _te('', 'Cancel') ?>" />
    </td>
</tr>
</table>
<?php } ?>
</form>