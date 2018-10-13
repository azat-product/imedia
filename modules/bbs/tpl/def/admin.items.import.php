<?php
/**
 * @var $this BBS
 */
tpl::includeJS(array('autocomplete'), true);
tplAdmin::adminPageSettings(array('icon' => false));
$aLang = $this->locale->getLanguages(false);
echo tplAdmin::blockStart(_t('bbs', 'Объявления / Импорт / Экспорт'), false);
?>
<input type="hidden" name="tab-current" class="j-tab-current" value="<?= $tab_form ?>">
<div class="tabsBar j-tabs">
    <? foreach($aData['tabs'] as $k => $v){ ?>
    <span class="tab j-tab<? if($k == $tab_form){ ?> tab-active<? } ?>" data-tab="<?= HTML::escape($k) ?>"><?= $v['t'] ?></span>
    <? } ?>
    <div class="progress" style="margin-left: 5px; display: none;" id="form-progress"></div>
</div>

<table class="admtbl tbledit j-mainFields">
    <tr>
        <td class="row1 field-title" width="120"><?= _t('bbs', 'Категория:'); ?></td>
        <td class="row2">
            <? foreach($cats as $lvl => $v):
            ?><select class="cat-select" autocomplete="off" style="margin: 0 5px 7px 0;"><?= $v['cats'] ?></select><?
            endforeach;
            ?>
        </td>
    </tr>
    <tr <? if (sizeof($aLang) <= 1){ ?>class="hidden"<? } ?>>
        <td class="row1"><?= _t('bbs', 'Локализация:'); ?></td>
        <td>
            <select name="language" class="j-language-select" style="width:100px;">
                <? foreach($aLang as $lngId => $lng) { ?>
                <option value="<?= $lngId ?>"><?= $lng['title'] ?></option>
                <? } ?>
            </select>
        </td>
    </tr>
</table>
<div id="j-services-tabs-content">
    <div id="j-tab-import" class="j-tab-form hidden">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="cat_id" id="j-cat_id" value="" />
            <table class="admtbl tbledit" style="margin-top:5px;">
                <tr>
                    <td class="row1 field-title" width="120"><?= _t('users', 'Пользователь'); ?>:<span class="required-mark j-multi_users j-multi_users-0">*</span></td>
                    <td>
                        <div style="margin:0 0 5px 4px;">
                            <label class="radio inline"><input type="radio" name="multi_users" value="0" checked="checked" /><?= _t('bbs', 'один пользователь'); ?></label>
                            <label class="radio inline"><input type="radio" name="multi_users" value="1" /><?= _t('bbs', 'несколько пользователей'); ?></label>
                            <i class="icon-question-sign disabled j-tooltip" data-placement="right" data-toggle="tooltip" title="<?= _te('bbs', 'E-mail пользователя указывается в объявлении'); ?>"></i>
                        </div>
                        <input type="hidden" name="user_id" value="0" id="j-item-user-id" />
                        <input type="text" name="email" value="" id="j-item-user-email" class="autocomplete j-multi_users j-multi_users-0 input-large" placeholder="<?= _te('bbs', 'Введите e-mail пользователя'); ?>" />
                        <label class="j-multi_users j-multi_users-1 checkbox displaynone"><input type="checkbox" name="multi_users_fake" value="1" checked="checked" /><?= _t('bbs', 'Пометить создаваемых пользователей как сгенерированные.'); ?></label>
                        <? if( BBS::publisher(BBS::PUBLISHER_SHOP) ) { ?>
                        <a href="javascript:void(0);" id="j-item-user-help" data-placement="right" data-toggle="tooltip" title="<?= _te('bbs', 'только пользователи с открытыми магазинами'); ?>"><i class="icon-question-sign"></i></a>
                        <script type="text/javascript">$(function () {
                                if (bff.bootstrapJS()) {
                                    $('#j-item-user-help').tooltip();
                                }
                            });</script>
                        <? } else if( BBS::publisher(BBS::PUBLISHER_USER_OR_SHOP) ) { ?>
                        <div id="j-item-user-publisher" class="j-multi_users j-multi_users-0" style="display: none; margin:5px;">
                            <label class="inline radio"><input type="radio" name="shop" value="0" checked="checked" /><?= _t('users', 'Частное лицо'); ?></label>
                            <label class="inline radio"><input type="radio" name="shop" value="1" /><?= _t('users', 'Магазин'); ?></label>
                        </div>
                        <? } else if( BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP) ) { ?>
                        <div id="j-item-user-publisher" class="j-multi_users j-multi_users-0" style="display: none; margin:5px;">
                            <label class="inline radio"><input type="radio" name="shop" value="0" /><?= _t('users', 'Частное лицо'); ?></label>
                            <label class="inline radio"><input type="radio" name="shop" value="1" checked="checked" /><?= _t('users', 'Магазин'); ?></label>
                        </div>
                        <? } ?>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title"><?= _t('bbs', 'Статус объявлений:'); ?></td>
                    <td style="height: 27px; padding-left: 7px;">
                        <label class="radio inline"><input type="radio" name="state" value="<?= BBS::STATUS_PUBLICATED ?>" checked="checked" /><?= _t('bbs', 'опубликованы'); ?></label>
                        <label class="radio inline"><input type="radio" name="state" value="<?= BBS::STATUS_PUBLICATED_OUT ?>" /><?= _t('bbs', 'сняты с публикации'); ?></label>
                    </td>
                </tr>
                <? if(BBS::formPublicationPeriod()): $publicationPeriodOpts = $this->publicationPeriodOptions($daysDefault); ?>
                    <tr class="j-publicate-period">
                        <td class="row1 field-title"><?= _t('bbs', 'Период публикации:'); ?></td>
                        <td style="height: 27px; padding-left: 7px;">
                            <select name="publicate_period" class="input-small"><?= HTML::selectOptions($publicationPeriodOpts, $daysDefault, false, 'days', 't') ?></select>
                        </td>
                    </tr>
                <? endif; ?>
                <? if(BBS::importUrlEnabled()): ?>
                <tr class="j-import-types">
                    <td class="row1 field-title"><?= _t('bbs', 'Источник:'); ?></td>
                    <td style="height: 27px; padding-left: 7px;">
                        <label class="radio inline"><input type="radio" class="j-import-type" name="type" value="<?= BBSItemsImport::TYPE_FILE ?>" checked="checked" /><?= _t('bbs', 'файл'); ?></label>
                        <label class="radio inline"><input type="radio" class="j-import-type" name="type" value="<?= BBSItemsImport::TYPE_URL ?>" /><?= _t('bbs', 'ссылка на файл'); ?></label>
                    </td>
                </tr>
                <tr class="j-url-import hide" >
                    <td class="row1 field-title"><?= _t('bbs', 'URL:'); ?></td>
                    <td style="height: 27px; padding-left: 7px;">
                        <input type="text" name="url" value=""  class="stretch" placeholder="<?= _te('bbs', 'Укажите URL файла импорта, например http://example.com/import.xml') ?>" maxlength="1500" />
                    </td>
                </tr>
                <tr class="j-url-import hide" >
                    <td class="row1 field-title"><?= _t('bbs', 'Период обработки:'); ?></td>
                    <td style="height: 27px; padding-left: 7px;">
                        <select name="period"><?= BBSItemsImport::importPeriodOptions(true) ?></select>
                    </td>
                </tr>
                <? endif;  ?>
                <? bff::hook('bbs.admin.import.form.import', array('data'=>&$aData)); ?>
                <tr class="j-file-import" >
                    <td class="row1 field-title"><?= _t('bbs', 'Файл импорта:'); ?></td>
                    <td>
                        <div class="form-upload">
                            <div class="upload-file">
                                <table>
                                    <tbody class="desc">
                                        <tr><td>
                                                <div class="upload-btn">
                                                    <span class="upload-mask">
                                                        <input type="file" name="file" id="j-import_file" />
                                                    </span>
                                                    <a class="ajax"><?= _t('bbs', 'выбрать файл'); ?> <span class="desc"><?= _t('bbs', '(*.xml, *.yml или *.csv формат)'); ?></span></a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody></table>
                                <div class="upload-res" id="j-import_file_cur"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <input type="submit" class="btn btn-small btn-success" id="j-import" disabled="disabled" value="<?= _te('bbs', 'Импортировать'); ?>" style="margin: 10px 0 0;" />
            <input type="button" class="btn btn-small j-import-getFile" disabled="disabled" onclick="jImport.doImport('xml'); return false;" value="<?= _te('bbs', 'Скачать XML шаблон'); ?>" style="margin: 10px 0 0;" />
            <input type="button" class="btn btn-small j-import-getFile" disabled="disabled" onclick="jImport.doImport('csv'); return false;" value="<?= _te('bbs', 'Скачать CSV шаблон'); ?>" style="margin: 10px 0 0;" />
        </form>
    </div>
    <div id="j-tab-export" class="j-tab-form hidden">
        <form action="" method="post">
            <table class="admtbl tbledit" style="margin-top:5px;">
                <tr>
                    <td class="row1 field-title" width="120"><?= _t('bbs', 'Статус объявлений:'); ?></td>
                    <td>
                        <label class="radio inline"><input type="radio" name="state" value="0" checked="checked" /><?= _t('', 'все'); ?></label>
                        <label class="radio inline"><input type="radio" name="state" value="1" /><?= _t('bbs', 'только опубликованные'); ?></label>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title" width="120"><?= _t('bbs', 'Формат:'); ?></td>
                    <td>
                        <label class="radio inline"><input type="radio" name="extension" value="xml" checked="checked" /><?= _t('', 'XML'); ?></label>
                        <label class="radio inline"><input type="radio" name="extension" value="csv"/><?= _t('', 'CSV'); ?></label>
                    </td>
                </tr>
                <? bff::hook('bbs.admin.import.form.export', array('data'=>&$aData)); ?>
            </table>
            <div class="hidden alert alert-warning" id="j-warning-Info" style="margin-top:10px;"></div>
            <div class="left" style="margin: 10px 0 0;">
                <input type="button" class="btn btn-small btn-success" id="j-import-export" disabled="disabled" onclick="jImport.doExport(); return false;" value="<?= _te('bbs', 'Экспортировать'); ?>" />
                <div class="help-inline desc hidden" id="j-exportDesc"><?= _t('bbs', 'Будет выгружено:'); ?> <span class="j-counter"></span></div>
            </div>
            <div class="clear"></div>
        </form>
    </div>
    <div id="j-tab-yandex" class="j-tab-form hidden"><?= $this->viewPHP($aData, 'admin.items.import.ym'); ?></div>
    <? bff::hook('bbs.admin.import.tabs.content', array('data'=>&$aData,'tabs'=>$aData['tabs'])); ?>
</div>
<?= tplAdmin::blockStop(); ?>
<?= $periodic ?>
<?= tplAdmin::blockStart(_t('bbs', 'Импорт объявлений'), false, array('id' => 'BbsImportsListBlock')); ?>
<div class="tabsBar" id="j-imports-tabs">
    <? foreach (array('admin' => _t('bbs', 'Администраторы'), 'user'=>_t('users', 'Пользователи')) as $k=>$v) { ?>
            <span class="tab j-tab<? if($k == $tab_list){ ?> tab-active<? } ?>" data-tab="<?= $k ?>"><?= $v ?></span>
    <? } ?>
    <div class="progress" style="margin-left: 5px; display: none;" id="BbsImportsProgress"></div>
</div>
<div id="j-imports-tabs-content">
    <div class="actionBar">
        <form method="get" action="<?= $this->adminLink(NULL) ?>" id="BbsImportsListFilters" onsubmit="return false;" class="form-inline">
            <input type="hidden" name="s" value="<?= bff::$class ?>" />
            <input type="hidden" name="ev" value="<?= bff::$event ?>" />
            <input type="hidden" name="page" value="<?= $f['page'] ?>" />
            <input type="hidden" name="tab_list" value="<?= $tab_list ?>" />
            <label class="relative">
                <input type="hidden" name="uid" id="j-imports-user-id" value="0">
                <input type="text" name="uemail" class="autocomplete" id="j-imports-user" style="width: 160px;" placeholder="<?= _te('bbs', 'ID / E-mail пользователя'); ?>" value="" autocomplete="off">
             </label>
            <input type="button" class="btn btn-small button cancel" onclick="jBbsImportsList.submitFilter();" value="<?= _te('', 'фильтровать'); ?>">
            <a class="ajax cancel" onclick="jBbsImportsList.submitFilter(true); return false;">сбросить</a>
            <div class="clear"></div>
        </form>
    </div>
    <table class="table table-condensed table-hover admtbl tblhover" id="BbsImportsListTable">
        <thead>
            <tr class="header nodrag nodrop">
                <th width="25"><?= _t('', 'ID'); ?></th>
                <th width="125" class="left"><?= _t('bbs', 'Категория'); ?></th>
                <th width="90"><?= _t('bbs', 'Обработано'); ?></th>
                <th class="left"><?= _t('bbs', 'Комментарий'); ?></th>
                <th width="100"><?= _t('bbs', 'Статус'); ?></th>
                <th width="120"><?= _t('', 'Created'); ?></th>
                <th width="100"><?= _t('', 'Action') ?></th>
            </tr>
        </thead>
        <tbody id="BbsImportsList">
            <?= $list ?>
        </tbody>
    </table>
    <div id="BbsImportsListPgn"><?= $pgn ?></div>
</div>
<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
    var jImport = (function () {
        var $progress, $langSelect, currentTab, $catField;
        var $templateButton = $('.j-import-getFile');
        var $exportButton = $('#j-import-export');
        var $importButton = $('#j-import');
        var catID = 0;
        var $importForm = $('#j-tab-import').find('form:eq(0)');
        var $importType = $importForm.find('.j-import-types');
        var $exportForm = $('#j-tab-export').find('form:eq(0)');
        var $exportInfo = $('#j-exportDesc');
        var $warningAlert = $('#j-warning-Info');
        var $fileField = $('#j-import_file');
        var $urlField = $('input[name=url]');
        var $curFileName = $('#j-import_file_cur');

        $(function(){
            $progress = $('#form-progress');
            $langSelect = $('.j-language-select');
            $catField = $('#j-cat_id');

            $('.j-tabs').on('click', '.j-tab', function () {
                onTab($(this).data('tab'), this);
            });

            $('.j-mainFields').on('change', '.cat-select', function () {
                catSelect($(this));
                buttonsState();
            });

            $langSelect.on('change', function () {
                buttonsState();
            });

            $exportForm.on('change', 'input[name=state]', function () {
                jImport.doExport(true);
            });

            $importForm.on('click', 'a.j-import-file-cancel', function () {
                $curFileName.html('');
                $fileField.val('');
                buttonsState();
            });

            $importForm.on('change',$urlField,function () {
                buttonsState();
            });

            $importType.on('click','.j-import-type',function(){
                var $el = $(this);
                switch ($el.val()){
                    case '<?= BBSItemsImport::TYPE_FILE ?>':
                        $('.j-file-import',$importForm).removeClass('hide');
                        $('.j-url-import',$importForm).addClass('hide');
                        break;
                    case '<?= BBSItemsImport::TYPE_URL ?>':
                        $('.j-file-import',$importForm).addClass('hide');
                        $('.j-url-import',$importForm).removeClass('hide');
                        break;
                }
            });

            $('#j-item-user-email').autocomplete('<?= $this->adminLink('ajax&act=item-user') ?>',
                    {valueInput: $('#j-item-user-id'), onSelect: function (id, title, ex) {
                            var $publisher = $('#j-item-user-publisher');
                            if ($publisher.length)
                                $publisher.toggle(intval(ex.data[2]) > 0);
                        }});

            $fileField.on('change', function () {
                var file = this.value.split("\\");
                file = file[file.length - 1];
                if (file.length > 30)
                    file = file.substring(0, 30) + '...';
                var html = '<a href="javascript:void(0);" class="j-import-file-cancel"></a>' + file;
                $curFileName.html(html);

                buttonsState();
            });

            bff.iframeSubmit($importForm, function (data, errors) {
                if (data && data.success) {
                    bff.success('<?= _t('bbs', 'Импортирование объявлений было успешно инициировано'); ?>');
                    jBbsImportsList.refreshAdminTab();
                } else if (errors) {
                    bff.error(errors);
                } else {
                    bff.error('<?= _t('bbs', 'Не удалось выполнить импорт'); ?>');
                }
            });

            <? if(BBS::formPublicationPeriod()): ?>
                var $period = $importForm.find('.j-publicate-period');
                $importForm.on('change', '[name="state"]', function(){
                    var v = intval($importForm.find('[name="state"]:checked').val());
                    $period.toggleClass('hide', v != <?= BBS::STATUS_PUBLICATED ?>);
                });
            <? endif; ?>

            onTab('<?= $tab_form ?>', 0, true);
            buttonsState();

            jImportYandex.init();

            $('.j-tooltip').tooltip();

            $importForm.on('change', '[name="multi_users"]', function () {
                $importForm.find('.j-multi_users').addClass('displaynone');
                $importForm.find('.j-multi_users-'+$(this).val()).removeClass('displaynone');
            });
        });

        function buttonsState()
        {
            $templateButton.prop('disabled', ! intval(catID));
            $exportButton.prop('disabled',  ! intval(catID));
            $importButton.prop('disabled', ! $fileField.val() && ! $urlField.val());
        }

        function catSelect($select)
        {
            catID = intval($select.val());

            $select.nextAll().remove();

            if (catID <= 0 && $select.prev('.j-cat-select').length == 0) {
                $catField.val('');
                jImport.doExport(true);
                return;
            }
            else {
                if (catID <= 0)
                    catID = $select.prev('.j-cat-select').val();
                $catField.val(catID);
                jImport.doExport(true);
            }

            bff.ajax('<?= $this->adminLink('ajax&act=item-form-cat'); ?>', {cat_id: catID}, function (data) {
                if (data.subs > 0) {
                    $select.after('<select class="cat-select" autocomplete="off" style="margin: 0 5px 7px 0;">' + data.cats + '</select>');
                }
            }, $progress);
        }

        function onTab(tab, link, onload)
        {
            if (currentTab == tab)
                return;

            $('.j-tab-form').hide();
            $('#j-tab-' + tab).show();
            $('#BbsImportsListBlock').toggleClass('hide', tab != 'import');
            $('#BbsImportsPeriodicListBlock').toggleClass('hide', tab != 'import');
            $('.j-mainFields').toggleClass('hide', tab == 'yandex');

            bff.onTab(link);
            currentTab = tab;
            $('.j-tab-current').val(tab);

            if (bff.h && onload!==true) {
                window.history.pushState({}, document.title, '<?= $this->adminLink(bff::$event) ?>&tab=' + tab);
            }
        }

        return {
            doImport: function (template) {
                if (template) {
                    var link = "<?= $this->adminLink('import'); ?>&act=import-template&catId=" + catID + "&langKey=" + $langSelect.val() + "&extension=" + template + "&" + $importForm.serialize();
                    bff.redirect(link);
                } else {
                    $importForm.submit();
                }
            },
            doExport: function (count) {
                var link = "<?= $this->adminLink('import&act=export&catId='); ?>" + catID + "&langKey=" + $langSelect.val() + "&" + $exportForm.serialize();
                if (count) {
                    $exportInfo.addClass('hidden').find('.j-counter').html('');
                    $warningAlert.hide();
                    if (catID > 0) {
                        link += '&count=true';
                        bff.ajax(link, {}, function (data) {
                            if (data.warning) {
                                $warningAlert.html(data.warning).show();
                            }
                            if (data.count) {
                                $exportInfo.removeClass('hidden').find('.j-counter').html(data.count);
                            }
                        }, $progress);
                    }
                } else {
                    bff.redirect(link);
                }
            },
            onTab: onTab
        }
    }());

    var jBbsImportsList = (function()
    {
        var $progress, $tabs, $block, $list, $listTable, $listPgn, filters, currentTab, $userId, $userEmail, processing = false;
        var ajaxUrl = '<?= $this->adminLink(bff::$event . '&act='); ?>';

        $(function () {
            $progress = $('#BbsImportsProgress');
            $block = $('#BbsImportsListBlock');
            $tabs = $('#j-imports-tabs');
            $list = $block.find('#BbsImportsList');
            $listTable = $block.find('#BbsImportsListTable');
            $listPgn = $block.find('#BbsImportsListPgn');
            filters = $block.find('#BbsImportsListFilters').get(0);
            $userId = $block.find('#j-imports-user-id');
            $userEmail = $block.find('#j-imports-user');

            $list.on('click', 'a.item-del', function () {
                var id = intval($(this).attr('rel'));
                if (id > 0 && bff.confirm('sure'))
                    del(id, this);
                return false;
            });

            $tabs.on('click', '.j-tab', function () {
                onTab($(this).data('tab'), this);
            });

            $userEmail.autocomplete('<?= $this->adminLink('ajax&act=item-user') ?>',
                {valueInput: $userId, minChars: 1}
            );
        });

        function isProcessing()
        {
            return processing;
        }

        function setProcessing(p) {
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
            filters.page.value = intval(id);
        }

        function del(id, link)
        {
            var f = [];
            bff.ajax(ajaxUrl + 'import-cancel&id=' + id, f, function (data) {
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

            filters.tab_list.value = tab;
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
            periodic: function(id)
            {
                $userId.val('');
                $userEmail.val('parent:' + id);
                setPage(0);
                updateList();
            },
            del: del
        };
    }());
</script>