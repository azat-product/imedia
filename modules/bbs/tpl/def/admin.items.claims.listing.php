<?
    $aTabs = array(
        0 => _t('bbs', 'Необработанные'),
        1 => _t('', 'Все'),
    );
?>

<div class="tabsBar" id="j-bbs-claims-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$status ? ' tab-active' : '' ?>"><a href="#" class="j-tab" data-id="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>

<div class="actionBar">
    <form action="" name="filter" class="form-inline" id="j-bbs-claims-filter" style="margin-left: 15px;">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="status" value="<?= bff::$event ?>" class="j-status-id" />
        <div class="controls controls-row">
            <input type="text" name="item" placeholder="<?= _te('bbs', 'ID объявления'); ?>" value="<?= ($item > 0 ? $item : '') ?>" class="input-medium" />
            <input type="submit" class="btn btn-small button submit" value="<?= _te('', 'найти'); ?>" />
            <label class="pull-right"><?= _t('', 'по:'); ?> <select name="perpage" class="j-perpage" style="width: 50px;"><?= $perpage ?></select></label>
            <div class="clearfix"></div>
        </div>
    </form>
</div>

<?= $this->viewPHP($aData, 'admin.items.claims'); ?>

<?= $pgn; ?>