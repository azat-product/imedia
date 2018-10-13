<?php
/**
 * Таб: Список модулей
 * @var $this Plugin_Setka_p00ae31
 */

$modulesList = $this->modulesList();
$modules = array();
if ( ! empty($config['modules'])) {
    $modules = func::unserialize($config['modules']);
}

?>
<div style="margin-bottom: 20px;" id="j-modules-settings-block">
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title" width="120px">
                Доступно в модуле:
            </td>
            <td class="row2">
                <? foreach($modulesList as $k => $v): ?>
                <label class="checkbox">
                    <input type="checkbox" name="modules[<?= $k ?>]" value="1" <?= ! empty($modules[ $k ]['enabled']) ? 'checked="checked"' : '' ?> />
                    <?= $v['t'] ?>
                </label>
                <? endforeach; ?>
            </td>
        </tr>
    </table>
</div>

