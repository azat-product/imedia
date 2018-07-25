<?php
    $bUsePadding = (stripos(Request::userAgent(), 'chrome') === false);
    $bEmptyOpt = ($mEmptyOpt !== false);
    if ($bEmptyOpt) {
        $nValue = 0;
        if (is_array($mEmptyOpt)) {
            $nValue = key($mEmptyOpt);
            $mEmptyOpt = current($mEmptyOpt);
        }
    }
?>
<? if($bEmptyOpt): ?>
    <option value="<?= $nValue ?>" class="bold"><?= $mEmptyOpt ?></option>
<? endif; ?>
<? foreach ($cats as $v): ?>
    <option value="<?= $v['id']?>" data-pid="<?= $v['pid'] ?>" data-numlevel="<?= $v['numlevel'] ?>"
        <? if($bUsePadding && $v['numlevel'] > 1): ?>
            style="padding-left: <?=($v['numlevel'] * 10)?>px"
        <? endif; ?>
        <? if($nSelectedID == $v['id']): ?>
            selected="selected"
        <? endif; ?>
        <? if($v['disabled']):?>
                disabled="disabled"
        <? endif; ?>
    >
        <?= !$bUsePadding && $v['numlevel'] > 1 ? str_repeat('&nbsp;&nbsp;', $v['numlevel']) : '' ?>
        <?= $v['title'] ?>
    </option>
<? endforeach; ?>