<?php
/**
 * @var $this \bff\db\Sphinx
 * @var $ajaxUrl string
 * @var $isRunning boolean
 * @var $f array
 * @var $list string
 * @var $pgn string
 * @var $separator string
 */

if ( ! $isRunning) { ?>
    <div class="alert alert-error" style="margin-bottom: 10px;"><?= _t('sphinx', 'Для работы данной функции вам необходимо подключить поисковую систему Sphinx.') ?></div>
<?php } else { ?>
<div class="well well-small" style="margin-bottom: 10px;"><?= _t('sphinx', 'Изменения данного списка начнут отражаться на результатах поиска в течении суток.') ?></div>
<?php } ?>
<div id="WordformsListBlock">
<div class="actionBar">
    <form method="post" action="<?= $ajaxUrl ?>" id="WordformsListFilters" onsubmit="return false;" class="form-inline">
        <input type="hidden" name="page" value="<?= $f['page'] ?>" />
        <input type="hidden" name="order" value="<?= HTML::escape($f['order']) ?>" />

        <div class="btn-group left">
            <a class="btn btn-small btn-success dropdown-toggle" data-toggle="dropdown" href="#">
                <?= _t('', '+ добавить') ?>
                <span class="caret"></span>
            </a>
            <ul class="dropdown-menu pull-left">
                <li><a href="javascript:" class="j-wordform-add"><?= _t('sphinx', 'Одну словоформу') ?></a></li>
                <li><a href="javascript:" class="j-wordform-many"><?= _t('sphinx', 'Несколько словоформ') ?></a></li>
            </ul>
        </div>
        <div class="left" style="margin-left:6px;">
            <input style="width:150px;" type="text" maxlength="150" name="q" placeholder="<?= _te('sphinx', 'словоформа'); ?>" value="<?= HTML::escape($f['q']) ?>" />
        </div>
        <div class="left" style="margin-left: 4px;">
            <div class="btn-group">
                <input type="submit" class="btn btn-small" onclick="jSphinxWordformsList.submit(false);" value="<?= _te('', 'search') ?>" />
                <a class="btn btn-small" onclick="jSphinxWordformsList.submit(true); return false;" title="<?= _te('', 'reset') ?>"><i class="disabled icon icon-refresh"></i></a>
            </div>
        </div>
        <div class="right">
        </div>
        <div class="clear"></div>
    </form>
</div>
<div class="j-wordform-textarea-block well well-small hide">
    <form method="post" action="">
        <input type="hidden" name="act" value="sphinx-wordforms-many" />
        <input type="hidden" name="save" value="1" />
        <textarea name="text" rows="5" placeholder="<?= _te('sphinx', 'Указывайте с каждой новой строки синоним и оригинальное слово, разделяя их знаком "[separator]", например: мазда [separator] mazda', array('separator'=>$separator)) ?>"></textarea>
        <a class="btn btn-small btn-success j-wordform-many-submit" href="javascript:"><?= _t('', 'Submit') ?></a>
        <a class="btn btn-small j-wordform-many-cancel" href="javascript:"><?= _t('', 'Cancel') ?></a>
    </form>
</div>

<table class="table table-condensed table-hover admtbl tblhover" id="WordformsListTable">
    <thead>
    <tr class="header nodrag nodrop">
        <th width="70">
            <a href="javascript: jSphinxWordformsList.onOrder('id');" class="ajax">ID</a>
            <div class="order-<?= $f['order_dir'] ?>" <? if($f['order_by']!='id') { ?>style="display:none;"<? } ?> id="wordforms-order-id"></div>
        </th>
        <th class="left">
            <a href="javascript: jSphinxWordformsList.onOrder('src');" class="ajax"><?= _t('sphinx', 'Синоним') ?></a>
            <div class="order-<?= $f['order_dir'] ?>" <? if($f['order_by']!='src') { ?>style="display:none;"<? } ?> id="wordforms-order-src"></div>
        </th>
        <th class="left">
            <a href="javascript: jSphinxWordformsList.onOrder('dest');" class="ajax"><?= _t('sphinx', 'Оригинал') ?></a>
            <div class="order-<?= $f['order_dir'] ?>" <? if($f['order_by']!='dest') { ?>style="display:none;"<? } ?> id="wordforms-order-dest"></div>
        </th>
        <th width="90"><?= _t('', 'Action') ?></th>
    </tr>
    </thead>
    <tbody id="WordformsList">
    <?= $list ?>
    </tbody>
</table>
<div id="WordformsListPgn"><?= $pgn ?></div>

<script type="text/javascript">
var jSphinxWordformsList = (function() {
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false;
    var orders = <?= func::php2js($orders) ?>, orderby = '<?= $f['order_by'] ?>';

    $(function(){
        $progress  = $('#WordformsProgress');
        $block     = $('#WordformsListBlock');
        $list      = $block.find('#WordformsList');
        $listTable = $block.find('#WordformsListTable');
        $listPgn   = $block.find('#WordformsListPgn');
        filters    = $block.find('#WordformsListFilters').get(0);

        $list.on('click', '.j-del', function (e) {
            e.preventDefault();
            var id = intval($(this).data('id'));
            if(id>0) del(id, this);
        });

        $list.on('click', '.j-edit', function (e) {
            e.preventDefault();
            var $el = $(this);
            var id = intval($el.data('id'));
            var $bl = $el.closest('tr');

            bff.ajax('<?= $ajaxUrl ?>&act=sphinx-wordforms-edit', {id:id}, function (data) {
                if (data && data.html) {
                    $bl.after(data.html);
                    $bl.remove();
                }
            });
        });

        $list.on('click', '.j-form-cancel', function (e) {
            e.preventDefault();
            var $el = $(this);
            var $f = $el.closest('.j-wordform-form-bl').find('form');
            var id = intval($f.find('[name="id"]').val());
            if (id) {
                bff.ajax('<?= $ajaxUrl ?>&act=sphinx-wordforms-data', {id:id}, function (data) {
                    if (data && data.html) {
                        var $bl = $el.closest('.j-wordform-form-bl');
                        $bl.after(data.html);
                        $bl.remove();
                    }
                });
            } else {
                $el.closest('.j-wordform-form-bl').remove();
                $list.find('.j-empty').show();
            }
        });

        $list.on('click', '.j-form-submit', function (e) {
            e.preventDefault();
            var $el = $(this);
            var $bl = $el.closest('.j-wordform-form-bl');
            var $f = $bl.find('form');
            bff.ajax('<?= $ajaxUrl ?>', $f.serialize(), function (data) {
                if (data && data.success) {
                    $bl.after(data.html);
                    $bl.remove();
                }
            });
        });

        $block.on('click', '.j-wordform-add', function (e) {
            e.preventDefault();
            bff.ajax('<?= $ajaxUrl ?>&act=sphinx-wordforms-add', {}, function (data) {
                if (data && data.html) {
                    var $fst = $list.find('tr:first');
                    if ($fst.length) {
                        $list.find('tr:first').before(data.html);
                        $list.find('.j-empty').hide();
                    } else {
                        $list.html(data.html);
                    }

                }
            });
        });

        $block.on('click', '.j-wordform-many', function (e) {
            e.preventDefault();
            $block.find('.j-wordform-textarea-block').removeClass('hide');
        });

        $block.on('click', '.j-wordform-many-cancel', function (e) {
            e.preventDefault();
            $block.find('.j-wordform-textarea-block').addClass('hide');
        });

        $block.on('click', '.j-wordform-many-submit', function (e) {
            e.preventDefault();
            var $f = $block.find('.j-wordform-textarea-block').find('form');
            bff.ajax('<?= $ajaxUrl ?>&act=sphinx-wordforms-many', $f.serialize(), function(data) {
                if (data && data.success) {
                    $f.find('[name="text"]').val('');
                    if (data.msg) {
                        bff.success(data.msg);
                    }
                    updateList();
                }
            });
        });

    });

    function isProcessing()
    {
        return processing;
    }

    function del(id, link)
    {
        bff.ajaxDelete('Удалить?', id, '<?= $ajaxUrl ?>&act=sphinx-wordforms-delete&id='+id, link, {progress: $progress, repaint: false});
        return false;
    }

    function updateList(updateUrl)
    {
        if(isProcessing()) return;
        var f = $(filters).serialize();
        bff.ajax('<?= $ajaxUrl ?>&act=sphinx-wordforms-list', f, function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
            }
        }, function(p){ $progress.toggle(); processing = p; $list.toggleClass('disabled'); });
    }

    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            setPage(1);
            if(resetForm) {
                filters['q'].value = '';
            }
            updateList();
        },
        page: function (id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
        },
        onOrder: function(by)
        {
            if(isProcessing() || !orders.hasOwnProperty(by))
                return;

            orders[by] = (orders[by] == 'asc' ? 'desc' : 'asc');
            $('#wordforms-order-'+orderby).hide();
            orderby = by;
            $('#wordforms-order-'+orderby).removeClass('order-asc order-desc').addClass('order-'+orders[by]).show();

            filters.order.value = orderby+'-'+orders[by];
            setPage(1);

            updateList();
        },
        refresh: function(resetPage, updateUrl)
        {
            if(resetPage) setPage(0);
            updateList(updateUrl);
        }
    };
}());
</script>
</div>