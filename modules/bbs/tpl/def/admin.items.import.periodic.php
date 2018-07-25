<?php
/**
 * @var $this BBS
 */
?>
<?= tplAdmin::blockStart(_t('bbs.import','Периодический импорт'), false, array('id' => 'BbsImportsPeriodicListBlock')); ?>
<div class="tabsBar" id="j-imports-periodic-tabs">
    <? foreach (array('admin' => _t('bbs', 'Администраторы'), 'user'=>_t('bbs', 'Пользователи')) as $k=>$v) { ?>
        <span class="tab j-tab<? if($k == $tab_list){ ?> tab-active<? } ?>" data-tab="<?= $k ?>"><?= $v ?></span>
    <? } ?>
    <div class="progress" style="margin-left: 5px; display: none;" id="BbsImportsPeriodicProgress"></div>
</div>
<div class="actionBar">
    <form method="get" action="<?= $this->adminLink(NULL) ?>" id="BbsImportsPeriodicListFilters" onsubmit="return false;" class="form-inline">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="p_page" value="<?= $f['p_page'] ?>" />
        <input type="hidden" name="p_tab_list" value="<?= $tab_list ?>" />
        <label class="relative">
            <input type="hidden" name="puid" id="j-imports-periodic-user-id" value="0">
            <input type="text" name="puemail" class="autocomplete" id="j-imports-periodic-user" style="width: 160px;" placeholder="<?= _te('bbs', 'ID / E-mail пользователя'); ?>" value="" autocomplete="off">
        </label>
        <input type="button" class="btn btn-small button cancel" onclick="jBbsImportsPeriodicList.submitFilter();" value="<?= _te('bbs', 'фильтровать'); ?>">
        <a class="ajax cancel" onclick="jBbsImportsPeriodicList.submitFilter(true); return false;"><?= _t('', 'сбросить'); ?></a>
        <div class="clear"></div>
    </form>
</div>
<table class="table table-condensed table-hover admtbl tblhover" id="BbsImportsPeriodicListTable">
    <thead>
    <tr class="header nodrag nodrop">
        <th width="25"><?= _t('', 'ID'); ?></th>
        <th class="left"><?= _t('bbs.import','URL'); ?></th>
        <th width="100"><?= _t('bbs.import','Период'); ?></th>
        <th width="100"><?= _t('bbs.import','Обработано'); ?></th>
        <th class="left"></th>
        <th width="100"><?= _t('bbs.import','Действие'); ?></th>
    </tr>
    </thead>
    <tbody id="BbsImportsPeriodicList">
    <?= $list ?>
    </tbody>
</table>
<div id="BbsImportsPeriodicListPgn"><?= $pgn ?></div>
<?= tplAdmin::blockStop(); ?>
<script type="text/javascript">
var jBbsImportsPeriodicList = (function()
    {
        var $progress, $tabs, $block, $list, $listTable, $listPgn, filters, currentTab, $userId, $userEmail, processing = false;
        var ajaxUrl = '<?= $this->adminLink('import_periodic' . '&act='); ?>';

        $(function () {
            $progress = $('#BbsImportsPeriodicProgress');
            $block = $('#BbsImportsPeriodicListBlock');
            $tabs = $('#j-imports-periodic-tabs');
            $list = $block.find('#BbsImportsPeriodicList');
            $listTable = $block.find('#BbsImportsPeriodicListTable');
            $listPgn = $block.find('#BbsImportsPeriodicListPgn');
            filters = $block.find('#BbsImportsPeriodicListFilters').get(0);
            $userId = $block.find('#j-imports-periodic-user-id');
            $userEmail = $block.find('#j-imports-periodic-user');

            $list.on('click', 'a.item-del', function () {
                var id = intval($(this).attr('rel'));
                if (id > 0 && bff.confirm('sure'))
                    del(id, this);
                return false;
            });

            $tabs.on('click', '.j-tab', function () {
                onTab($(this).data('tab'), this);
            });

            $list.on('click', '.j-periodic', function(e){
                e.preventDefault();
                jBbsImportsList.periodic($(this).data('id'));
            });

            $userEmail.autocomplete('<?= $this->adminLink('ajax&act=item-user') ?>',
                {valueInput: $userId, minChars: 1}
            );
        });

        function isProcessing()
        {
            return processing;
        }

        function setProcessing(p)
        {
            processing = p;
        }

        function updateList(updateUrl)
        {
            if (isProcessing())
                return;
            var f = $(filters).serialize();

            bff.ajax(ajaxUrl, f, function (data) {
                if (data) {
                    $list.html(data.list);
                    $listPgn.html(data.pgn);
                    if (updateUrl !== false && bff.h) {
                        window.history.pushState({}, document.title, $(filters).attr('action') + '?' + f);
                    }
                }
            }, function(p) {
                $progress.toggle();
                setProcessing(p);
                $list.toggleClass('disabled');
            });
        }

        function setPage(id)
        {
            filters.p_page.value = intval(id);
        }

        function del(id, link)
        {
            bff.ajax(ajaxUrl + 'import-delete&id=' + id, [], function (data) {
                if (data && data.success) {
                    setProcessing(false);
                    updateList();
                }
                return false;
            });
        }

        function onTab(tab, link)
        {
            if (currentTab == tab)
                return;

            filters.p_tab_list.value = tab;
            updateList();

            bff.onTab(link);
            currentTab = tab;
        }

        return {
            page: function (id)
            {
                if (isProcessing())
                    return false;
                setPage(id);
                updateList();
            },
            refreshAdminTab: function()
            {
                setPage(0);
                onTab('admin', $tabs.find('.j-tab[data-tab="admin"]'));
                updateList(false);
            },
            importInfo: function (itemID)
            {
                if (itemID) {
                    $.fancybox('', {ajax: true, href: '<?= $this->adminLink('ajax&act=import-info&id=') ?>' + itemID});
                }
                return false;
            },
            submitFilter: function (reset)
            {
                if(reset === true) {
                    $userEmail.val('');
                    $userId.val('');
                }
                setPage(0);
                updateList();
            },
            del: del
        };
    }());
</script>

