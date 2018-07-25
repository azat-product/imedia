<?php
?>
<tr class="j-wordform-form-bl">
    <td></td>
    <td colspan="2">
        <form method="post" action="">
            <input type="hidden" name="act" value="<?= $act ?>" />
            <input type="hidden" name="save" value="1" />
            <input type="hidden" name="id" value="<?= $id ?>" />
            <input type="text" name="src" value="<?= HTML::escape($src) ?>" placeholder="<?= _te('sphinx', 'Синоним') ?>" style="width: 48%" />
            <input type="text" name="dest" value="<?= HTML::escape($dest) ?>" placeholder="<?= _te('sphinx', 'Оригинал') ?>" style="width: 48%" />
        </form>
    </td>
    <td>
        <a class="but j-form-submit" title="<?= _te('', 'Submit') ?>" href="#" style="margin-top: -7px;"><i class="icon icon-ok disabled"></i></a>
        <a class="but j-form-cancel" title="<?= _te('', 'Cancel') ?>" href="#" style="margin-top: -7px;"><i class="icon icon-remove disabled"></i></a>
    </td>
</tr>
