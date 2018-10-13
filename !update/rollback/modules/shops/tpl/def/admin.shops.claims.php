<?php
    $aTabs = array(
        0 => _t('shops', 'Необработанные'),
        1 => _t('', 'Все'),
    );
?>

<div class="tabsBar" id="j-shops-claims-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$status ? ' tab-active' : '' ?>"><a href="#" class="j-tab" data-id="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>

<div class="actionBar">
    <form action="" name="filter" class="form-inline" id="j-shops-claims-filter" style="margin-left: 15px;">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="status" value="<?= $status ?>" class="j-status-id" />
        <div class="controls controls-row">
            <input type="text" name="shop" placeholder="<?= _te('shops', 'ID магазина'); ?>" value="<?= ($shop > 0 ? $shop : '') ?>" class="input-medium" />
            <input type="submit" class="btn btn-small button submit" value="<?= _te('', 'найти'); ?>" />
            <a class="cancel" id="j-shops-claims-filter-cancel"><?= _t('', 'сбросить'); ?></a>
            <label class="pull-right"><?= _t('', 'по:'); ?> <select name="perpage" class="j-perpage" style="width: 50px;"><?= $perpage ?></select></label>
            <div class="clearfix"></div>
        </div>
    </form>
</div>

<?= $this->viewPHP($aData, 'admin.shops.claims.list'); ?>

<?= $pgn; ?>

<script type="text/javascript">
    $(function(){
        var $filter = $('#j-shops-claims-filter');
        $('#j-shops-claims-filter-cancel').click(function(e){ nothing(e);
            var filter = $filter.get(0);
            filter.elements.shop.value = '';
            filter.submit();
        });
    });
</script>